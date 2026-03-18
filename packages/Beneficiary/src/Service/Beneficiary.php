<?php

namespace Solidarity\Beneficiary\Service;

use Solidarity\Beneficiary\Entity\PaymentMethod;
use Solidarity\Beneficiary\Entity\RegisteredPeriods;
use Solidarity\Beneficiary\Repository\BeneficiaryRepository;
use Skeletor\Core\TableView\Service\TableView;
use Psr\Log\LoggerInterface as Logger;
use Skeletor\User\Service\Session;
use Solidarity\Beneficiary\Filter\Beneficiary as BeneficiaryFilter;
use Doctrine\ORM\EntityManagerInterface;
use Solidarity\Delegate\Entity\Delegate;
use Solidarity\Transaction\Service\Project;

class Beneficiary extends TableView
{
    public function __construct(
        BeneficiaryRepository $repo, Session $user, Logger $logger, BeneficiaryFilter $filter,
        private EntityManagerInterface $entityManager, private Project $project
    ) {
        parent::__construct($repo, $user, $logger, $filter);
    }

    public function create(array $data)
    {
        // Run filter first so registeredPeriods is validated before extraction
        if ($this->filter) {
            $data = $this->filter->filter($data);
        }

        $registeredPeriodsData = $data['registeredPeriods'] ?? [];
        unset($data['registeredPeriods']);

        $entity = $this->repo->create($data);

        $this->syncRegisteredPeriods($entity->getId(), $registeredPeriodsData);

        return $entity;
    }

    public function update(array $data)
    {
        // Run filter first so registeredPeriods is validated before extraction
        if ($this->filter) {
            $data = $this->filter->filter($data);
        }

        $registeredPeriodsData = $data['registeredPeriods'] ?? [];
        unset($data['registeredPeriods']);

        $entity = $this->repo->update($data);

        $this->syncRegisteredPeriods($entity->getId(), $registeredPeriodsData);

        return $entity;
    }

    private function syncRegisteredPeriods(int $beneficiaryId, array $rows): void
    {
        // Delete existing registered periods for this beneficiary
        $existing = $this->entityManager->getRepository(RegisteredPeriods::class)
            ->findBy(['beneficiary' => $beneficiaryId]);
        foreach ($existing as $rp) {
            $this->entityManager->remove($rp);
        }
        $this->entityManager->flush();

        // Create new ones
        $beneficiary = $this->entityManager->getRepository(\Solidarity\Beneficiary\Entity\Beneficiary::class)
            ->find($beneficiaryId);

        foreach ($rows as $row) {
            $period = $this->entityManager->getRepository(\Solidarity\Period\Entity\Period::class)
                ->find($row['period']);
            if (!$period) {
                continue;
            }

            $project = !empty($row['project'])
                ? $this->entityManager->getRepository(\Solidarity\Transaction\Entity\Project::class)->find($row['project'])
                : $period->project;
            if (!$project) {
                continue;
            }

            $rp = new RegisteredPeriods();
            $rp->beneficiary = $beneficiary;
            $rp->period = $period;
            $rp->project = $project;
            $rp->amount = $row['amount'];
            $this->entityManager->persist($rp);
        }
        $this->entityManager->flush();
    }

    public function fetchTableData(
        $search, $filter, $offset, $limit, $order, $uncountableFilter = null, $idsToInclude = [], $idsToExclude = []
    ) {
        // delegate can only see own account
        if ($this->getUserSession()->getLoggedInEntityType() === 'delegate') {
            $uncountableFilter['createdBy'] = $this->getUserSession()->getLoggedInUserId();
        }
        $items = $this->repo->fetchTableData($search, $filter, $offset, $limit, $order, $uncountableFilter, $idsToInclude, $idsToExclude);
        return [
            'count' => $items['count'],
            'entities' => $this->prepareEntities($items['items']),
            'countColumnData' => $items['countColumnData']
        ];
    }

    public function prepareEntities($entities)
    {
        $items = [];
        // todo add total received (confirmed) amount
        foreach ($entities as $beneficiary) {
            // Sum amounts from registered periods
            $totalAmount = 0;
            $projects = [];
            foreach ($beneficiary->registeredPeriods as $rp) {
                $totalAmount += $rp->amount;
                $projects[$rp->project->id] = $rp->project->code;
            }
            $methods = '';
            foreach ($beneficiary->paymentMethods as $pm) {
                $methods .= $pm->project->code .': '. PaymentMethod::getHrType($pm->type) . ', ';
                if ($pm->accountNumber) {
                    $methods .= $pm->accountNumber;
                }
                $methods .= '<br>';
            }
            $itemData = [
                'id' => $beneficiary->getId(),
                'name' =>  [
                    'value' => $beneficiary->name,
                    'editColumn' => true,
                ],
                'pm.project' => implode(', ', $projects),
                'sumAmount' => $totalAmount,
                // TODO add message when delegate not existing
                'delegateVerified' => ($beneficiary->createdBy?->status === Delegate::STATUS_VERIFIED) ? 'Da' : 'Ne',
                'pm.accountNumber' => $methods,//$beneficiary->accountNumber,
                'status' => \Solidarity\Beneficiary\Entity\Beneficiary::getHrStatus($beneficiary->status),
                'createdBy' => $beneficiary->createdBy?->name,
                'createdAt' => $beneficiary->getCreatedAt()->format('d.m.Y'),
            ];
            $items[] = [
                'columns' => $itemData,
                'id' => $beneficiary->getId(),
            ];
        }
        return $items;
    }

    public function compileTableColumns()
    {
        return [
            ['name' => 'name', 'label' => 'Ime'],
//            ['name' => 'amount', 'label' => 'Trenutni iznos'],
            ['name' => 'pm.project', 'label' => 'Projekat', 'filterData' => $this->project->getFilterData()],
            ['name' => 'sumAmount', 'label' => 'Ukupan iznos'],
            ['name' => 'pm.accountNumber', 'label' => 'Metode plaćanja'],
            ['name' => 'status', 'label' => 'Status', 'filterData' => \Solidarity\Beneficiary\Entity\Beneficiary::getHrStatuses()],
            ['name' => 'delegateVerified', 'label' => 'Delegat verifikovan'],
            ['name' => 'createdBy', 'label' => 'Delegat'],
            ['name' => 'createdAt', 'label' => 'Kreirano'],
        ];
    }
}
