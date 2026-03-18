<?php
namespace Solidarity\Transaction\Factory;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Skeletor\Core\Factory\AbstractFactory;
use Solidarity\Donor\Entity\Donor;
use Solidarity\Transaction\Entity\Project;
use Solidarity\Transaction\Entity\Transaction;

class TransactionFactory extends AbstractFactory
{
    public static function compileEntityForCreate($data, $em): ?int
    {
        $transaction = new Transaction();
        $transaction->amount = $data['amount'];
        $transaction->status = $data['status'];
        $transaction->donorConfirmed = $data['donorConfirmed'];
        $transaction->donor = $em->getRepository(Donor::class)->find($data['donor']);
        $transaction->project = $em->getRepository(Project::class)->find($data['project']);
        $transaction->beneficiaryId = $data['beneficiaryId'];
        $transaction->beneficiaryType = $data['beneficiaryType'];

        $beneficiary = $em->getRepository($transaction->getBeneficiaryClass())->find($data['beneficiaryId']);
        $transaction->accountNumber = $beneficiary->accountNumber;

        $em->persist($transaction);
        $em->flush();

        return $transaction->id;
    }

    public static function compileEntityForUpdate($data, $em)
    {
        $transaction = $em->getRepository(Transaction::class)->find($data['id']);
        $transaction->status = $data['status'];
        $transaction->donorConfirmed = $data['donorConfirmed'];

        // @TODO maybe should not allow updating of acc no
//        $beneficiary = $em->getRepository($transaction->getBeneficiaryClass())->find($transaction->beneficiaryId);
//        $transaction->accountNumber = $beneficiary->accountNumber;

        $transaction->amount = $data['amount'];
        $transaction->comment = $data['comment'];

        return $transaction->id;
    }
}
