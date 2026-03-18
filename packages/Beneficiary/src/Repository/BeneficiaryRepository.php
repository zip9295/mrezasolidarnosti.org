<?php

namespace Solidarity\Beneficiary\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Solidarity\Beneficiary\Entity\Beneficiary;
use Solidarity\Beneficiary\Factory\BeneficiaryFactory;
use Skeletor\Core\TableView\Repository\TableViewRepository;

class BeneficiaryRepository extends TableViewRepository
{
    const ENTITY = Beneficiary::class;
    const FACTORY = BeneficiaryFactory::class;

    public function __construct(
        protected EntityManagerInterface $entityManager
    ) {
        parent::__construct($entityManager);
    }

    public function getJoinableEntities()
    {
        return ['paymentMethods' => 'pm'];
    }


    public function getSearchableColumns(): array
    {
        return ['a.name', 'a.status', 'pm.accountNumber', 'pm.wireInstructions'];
    }

    public function getColumnsToCount(): array
    {
        return [];
    }

    public function nullifyCreatedByForDelegate(int $delegateId): void
    {
        $conn = $this->entityManager->getConnection();
        $conn->executeStatement(
            'UPDATE beneficiary SET createdBy_id = NULL WHERE createdBy_id = ?',
            [$delegateId]
        );
    }

    public function assignOrphanedBeneficiariesToDelegate(int $schoolId, int $delegateId): void
    {
        $conn = $this->entityManager->getConnection();
        $conn->executeStatement(
            'UPDATE beneficiary SET createdBy_id = ? WHERE school_id = ? AND createdBy_id IS NULL',
            [$delegateId, $schoolId]
        );
    }
}
