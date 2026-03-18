<?php

namespace Solidarity\Transaction\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Skeletor\Core\Entity\Timestampable;
use Solidarity\Delegate\Entity\Delegate;
use Solidarity\Donor\Entity\Donor;
use Solidarity\Period\Entity\Period;

#[ORM\Entity]
#[ORM\Table(name: 'project')]
class Project
{
    use Timestampable;

    #[ORM\Column(type: Types::STRING, length: 128)]
    public string $name;

    #[ORM\Column(type: Types::STRING, length: 16)]
    public string $code;

    #[ORM\Column(type: Types::STRING, length: 256)]
    public string $logo;

    #[ORM\ManyToMany(targetEntity: Delegate::class, mappedBy: 'projects')]
    public Collection $delegates;

    #[ORM\OneToMany(targetEntity: Period::class, mappedBy: 'project')]
    public Collection $periods;

}