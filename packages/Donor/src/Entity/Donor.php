<?php

namespace Solidarity\Donor\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Skeletor\Core\Entity\Timestampable;
use Solidarity\Transaction\Entity\Project;
use Solidarity\Transaction\Entity\Transaction;

#[ORM\Entity]
#[ORM\Table(name: 'donor')]
class Donor
{
    use Timestampable;

    const STATUS_NEW = 1;
    const STATUS_VERIFIED = 2;
    const STATUS_PROBLEM = 3;

    const DONATE_TO_ALL = 1;
    const DONATE_TO_SCHOOL = 2;
    const DONATE_TO_UNI = 3;

    #[ORM\Column(type: Types::STRING, length: 255, unique: true)]
    public string $email;
    #[ORM\Column(type: Types::STRING, length: 128)]
    public string $firstName;
    #[ORM\Column(type: Types::STRING, length: 128)]
    public string $lastName;
    #[ORM\Column(type: Types::SMALLINT)]
    public int $status;
    #[ORM\Column(type: Types::SMALLINT)]
    public int $wantsToDonateTo;
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    public ?string $comment;
    #[ORM\Column(type: Types::INTEGER)]
    public string $isActive;
    #[ORM\Column(type: Types::STRING, length: 128, nullable: true)]
    public ?string $ipv4;
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    public ?\DateTime $lastLogin;

    #[ORM\OneToMany(targetEntity: PaymentMethod::class, mappedBy: 'donor')]
    public Collection $paymentMethods;
    #[ORM\OneToMany(targetEntity: Transaction::class, mappedBy: 'donor')]
    public Collection $transactions;
    #[ORM\ManyToMany(targetEntity: Project::class, inversedBy: 'donors')]
    #[ORM\JoinTable(name: 'donor_project')]
    public Collection $projects;

    public static function getHrStatuses(): array
    {
        return array(
            self::STATUS_NEW => 'New',
            self::STATUS_VERIFIED => 'Verified',
            self::STATUS_PROBLEM => 'Problem',
        );
    }

    public static function getHrStatus($status): string
    {
        return static::getHrStatuses()[$status];
    }

    public static function getHrDonationOptions(): array
    {
        return array(
            self::DONATE_TO_ALL => 'Svima',
            self::DONATE_TO_SCHOOL => 'Prosveti',
            self::DONATE_TO_UNI => 'Univerzitetima',
        );
    }

    public static function getHrDonationOption($option): string
    {
        return static::getHrDonationOptions()[$option];
    }
}