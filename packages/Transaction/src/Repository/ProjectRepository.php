<?php
namespace Solidarity\Transaction\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Solidarity\Transaction\Entity\Project;
use Solidarity\Transaction\Factory\ProjectFactory;
use Skeletor\Core\TableView\Repository\TableViewRepository;

class ProjectRepository extends TableViewRepository
{
    const ENTITY = Project::class;
    const FACTORY = ProjectFactory::class;

    public function __construct(
        protected EntityManagerInterface $entityManager
    ) {
        parent::__construct($entityManager);
    }

    public function getSearchableColumns(): array
    {
        return ['a.name'];
    }

}