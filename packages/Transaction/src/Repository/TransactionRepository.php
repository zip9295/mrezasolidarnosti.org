<?php
namespace Solidarity\Transaction\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Solidarity\Beneficiary\Entity\Beneficiary;
use Solidarity\Transaction\Entity\Transaction;
use Solidarity\Transaction\Factory\TransactionFactory;
use Skeletor\Core\TableView\Repository\TableViewRepository;

class TransactionRepository extends TableViewRepository
{
    const ENTITY = Transaction::class;
    const FACTORY = TransactionFactory::class;

    // tmp solution
    const PROJECT_MSP = 1;
    const PROJECT_MSPR = 2;

    public function __construct(
        protected EntityManagerInterface $entityManager
    ) {
        parent::__construct($entityManager);
    }

    public function getTransactionsBySchool($schoolId)
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('t')
            ->from(static::ENTITY, 't')
            ->join(Beneficiary::class, 'b', 'WITH', 't.beneficiary = b')
            ->where('b.school = :school')
            ->andWhere('t.project = :project');
        $qb->setParameter('school', $schoolId);
        $qb->setParameter('project', static::PROJECT_MSP);

        return $qb->getQuery()->getResult();
    }

    /**
     * Returns if overall limit donated per person is achieved.
     * Takes all projects into account.
     *
     * @param $donorEmail
     * @param $receiverName
     * @return bool
     */
    public function perPersonLimit($donorEmail, $receiverName)
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('SUM(a.amount)')
            ->from(static::ENTITY, 'a')
            ->where('a.email = :email')
            ->andWhere('a.name = :name');
        $qb->setParameter('email', $donorEmail);
        $qb->setParameter('name', $receiverName);

        return $qb->getQuery()->getSingleScalarResult() > Transaction::PER_PERSON_LIMIT;
    }

    /**
     * Resolves and assigns the beneficiary entity for each transaction.
     *
     * @param Transaction[] $transactions
     */
    public function resolveBeneficiaries(array $transactions): void
    {
        // Group beneficiary IDs by type for batch loading
        $grouped = [];
        foreach ($transactions as $transaction) {
            $grouped[$transaction->beneficiaryType][] = $transaction->beneficiaryId;
        }

        // Batch load each type
        $loaded = [];
        foreach ($grouped as $type => $ids) {
            $class = Beneficiary::class;
            $entities = $this->entityManager->getRepository($class)->findBy(['id' => array_unique($ids)]);
            foreach ($entities as $entity) {
                $loaded[$type][$entity->getId()] = $entity;
            }
        }

        // Assign to each transaction
        foreach ($transactions as $transaction) {
            $transaction->beneficiary = $loaded[$transaction->beneficiaryType][$transaction->beneficiaryId] ?? null;
        }
    }

    public function getSearchableColumns(): array
    {
        return ['a.amount', 'a.name', 'a.accountNumber', 'a.email'];
    }

    public function getColumnsToCount(): array
    {
        return ['amount'];
    }

}