<?php
namespace Solidarity\Delegate\Service;

use Solidarity\Delegate\Repository\DelegateRepository;
use Solidarity\Delegate\Entity\Delegate as DelegateEntity;
use Skeletor\Core\TableView\Service\TableView;
use Psr\Log\LoggerInterface as Logger;
use Skeletor\User\Service\Session;
use Solidarity\Delegate\Filter\Delegate as DelegateFilter;
use Solidarity\Mailer\Service\Mailer;
use Solidarity\Beneficiary\Repository\BeneficiaryRepository;
use Solidarity\School\Repository\SchoolTypeRepository;
use Solidarity\School\Service\SchoolType;
use Solidarity\Transaction\Service\Project;

class Delegate extends TableView
{

    /**
     * @param DelegateRepository $repo
     * @param Session $user
     * @param Logger $logger
     */
    public function __construct(
        DelegateRepository $repo, Session $user, Logger $logger, DelegateFilter $filter, private \DateTime $dt,
        private Mailer $mailer, private SchoolType $schoolType, private Project $project,
        private BeneficiaryRepository $beneficiaryRepo
    ) {
        parent::__construct($repo, $user, $logger, $filter);
    }

    public function getAffectedDelegates()
    {
        return $this->repo->getAffectedDelegates();
    }

    public function create(array $data)
    {
        $schoolValue = $data['school'] ?? null;
        $schoolId = is_array($schoolValue) ? (int) ($schoolValue['id'] ?? 0) : (int) $schoolValue;
        $schoolId = $schoolId ?: null;

        $entity = parent::create($data);

        // Assign orphaned beneficiaries at this school to the new delegate
        if ($schoolId) {
            $this->beneficiaryRepo->assignOrphanedBeneficiariesToDelegate(
                $schoolId,
                $entity->getId()
            );
        }

        return $entity;
    }

    public function update(array $data)
    {
        $sendMail = $data['sendRoundStartMail'] ?? 0;
        unset($data['sendRoundStartMail']);
        if ($sendMail) {
            $data['formLinkSent'] = 1;
        }

        $delegateId = (int) $data['id'];
        $schoolValue = $data['school'] ?? null;
        $newSchoolId = is_array($schoolValue) ? (int) ($schoolValue['id'] ?? 0) : (int) $schoolValue;
        $newSchoolId = $newSchoolId ?: null;

        // Check if school is changing
        $oldEntity = $this->repo->getById($delegateId);
        $oldSchoolId = $oldEntity->school?->getId();

        // If school changed, nullify createdBy on beneficiaries of the old school
        if ($oldSchoolId && $oldSchoolId !== $newSchoolId) {
            $this->beneficiaryRepo->nullifyCreatedByForDelegate($delegateId);
        }

        $entity = parent::update($data);

        // Assign orphaned beneficiaries at the new school to this delegate
        if ($newSchoolId) {
            $this->beneficiaryRepo->assignOrphanedBeneficiariesToDelegate(
                $newSchoolId,
                $delegateId
            );
        }

        // @TODO remove debug
        file_put_contents(__DIR__ . '/delegate_debug.log', sprintf(
            "delegateId=%d, newSchoolId=%s, oldSchoolId=%s, data[school]=%s\n",
            $delegateId, var_export($newSchoolId, true), var_export($oldSchoolId, true), $data['school'] ?? 'NULL'
        ), FILE_APPEND);

        return $entity;
    }

    public function fetchTableData(
        $search, $filter, $offset, $limit, $order, $uncountableFilter = null, $idsToInclude = [], $idsToExclude = []
    ) {
        // delegate can only see own account
        if ($this->getUserSession()->getLoggedInEntityType() === 'delegate') {
            $uncountableFilter['id'] = $this->getUserSession()->getLoggedInUserId();
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
        /* @var \Solidarity\Delegate\Entity\Delegate $delegate */
        foreach ($entities as $delegate) {
            $projects = [];
            foreach ($delegate->projects as $project) {
                $projects[] = $project->code;
            }
            $itemData = [
                'id' => $delegate->getId(),
                'email' =>  [
                    'value' => $delegate->email,
                    'editColumn' => true,
                ],
                'name' => $delegate->name .' ('. implode(', ', $projects) . ')',
                'p.id' => implode(', ', $projects),
                'school' => $delegate->school?->name,
                'schoolType' => $delegate->school?->type->name,
                'phone' => $delegate->phone,
                'status' => \Solidarity\Delegate\Entity\Delegate::getHrStatus($delegate->status),
//                'updatedAt' => $delegate->getUpdatedAt()->format('d.m.Y'),
                'createdAt' => $delegate->getCreatedAt()->format('d.m.Y'),
            ];
            $items[] = [
                'columns' => $itemData,
                'id' => $delegate->getId(),
            ];
        }
        return $items;
    }

    public function compileTableColumns()
    {
        $columnDefinitions = [
            ['name' => 'email', 'label' => 'Email'],
            ['name' => 'name', 'label' => 'Ime'],
            ['name' => 'phone', 'label' => 'Telefon'],
            ['name' => 'p.id', 'label' => 'project', 'filterData' => $this->project->getFilterData()],
            ['name' => 'status', 'label' => 'Status', 'filterData' => \Solidarity\Delegate\Entity\Delegate::getHrStatuses()],
            ['name' => 'schoolType', 'label' => 'Tip škole', 'filterData' => $this->schoolType->getFilterData()],
            ['name' => 'school', 'label' => 'Škola'],
//            ['name' => 'city', 'label' => 'City'],
//            ['name' => 'updatedAt', 'label' => 'Updated at', 'priority' => 8],
            ['name' => 'createdAt', 'label' => 'Kreirano u', 'priority' => 9],
        ];

        return $columnDefinitions;
    }

}
