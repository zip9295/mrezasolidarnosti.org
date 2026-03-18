<?php

namespace Solidarity\Transaction\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Skeletor\Core\Entity\Timestampable;
use Solidarity\Beneficiary\Entity\Beneficiary;
use Solidarity\Donor\Entity\Donor;

#[ORM\Entity]
#[ORM\Table(name: 'transaction')]
class Transaction
{
    use Timestampable;

    const STATUS_NEW = 1;
    const STATUS_VERIFIED = 2;
    const STATUS_CONFIRMED = 3;
    const STATUS_CANCELLED = 4;

    const PER_PERSON_LIMIT = 30000;

    const BENEFICIARY_TYPE_EDUCATOR = 1;
    const BENEFICIARY_TYPE_BENEFICIARY = 2;

    // @todo one of these two must be entered; accNumber can contain phone number when using WU
    #[ORM\Column(type: Types::STRING, length: 32, nullable: true)]
    public ?string $accountNumber;
    // should contain textual instruction if required
    #[ORM\Column(type: Types::STRING, length: 512, nullable: true)]
    public ?string $instructions;
    #[ORM\Column(type: Types::INTEGER)]
    public int $amount;
    #[ORM\Column(type: Types::INTEGER)]
    public int $status;
    #[ORM\Column(type: Types::BOOLEAN)]
    public bool $donorConfirmed;
    #[ORM\Column(type: Types::STRING, length: 1024, nullable: true)]
    public ?string $comment;
    // payment code provided by the payment institution, entered by the donor when confirming payment
    #[ORM\Column(type: Types::STRING, length: 256, nullable: true)]
    public ?string $paymentCode;

    #[ORM\ManyToOne(inversedBy: 'transactions')]
    #[ORM\JoinColumn(name: 'projectId', referencedColumnName: 'id', unique: false, nullable: false)]
    public Project $project;

    #[ORM\ManyToOne(targetEntity: Donor::class, inversedBy: 'transactions')]
    #[ORM\JoinColumn(name: 'donorId', referencedColumnName: 'id', unique: false)]
    public Donor $donor;

    #[ORM\ManyToOne(targetEntity: Beneficiary::class, inversedBy: 'transactions')]
    #[ORM\JoinColumn(name: 'beneficiaryId', referencedColumnName: 'id', unique: false)]
    public Beneficiary $beneficiary;

    public static function getHrStatuses(): array
    {
        return array(
            self::STATUS_NEW => 'New',
            self::STATUS_VERIFIED => 'Verified',
            self::STATUS_CONFIRMED => 'Confirmed',
            self::STATUS_CANCELLED => 'Cancelled',
        );
    }
}