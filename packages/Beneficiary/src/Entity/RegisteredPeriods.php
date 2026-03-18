<?php

namespace Solidarity\Beneficiary\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Skeletor\Core\Entity\Timestampable;
use Solidarity\Period\Entity\Period;
use Solidarity\Transaction\Entity\Project;

#[ORM\Entity]
#[ORM\Table(name: 'beneficiaryRegisteredPeriods')]
class RegisteredPeriods
{
    use Timestampable;

    #[ORM\Column(type: Types::INTEGER)]
    public int $amount;

    #[ORM\ManyToOne(targetEntity: Period::class, inversedBy: 'beneficiaries')]
    #[ORM\JoinColumn(nullable: false)]
    public Period $period;

    #[ORM\ManyToOne(targetEntity: Project::class, inversedBy: 'beneficiaries')]
    #[ORM\JoinColumn(nullable: false)]
    public Project $project;

    #[ORM\ManyToOne(targetEntity: Beneficiary::class, inversedBy: 'registeredPeriods')]
    #[ORM\JoinColumn(nullable: false)]
    public Beneficiary $beneficiary;
}