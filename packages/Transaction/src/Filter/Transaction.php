<?php

namespace Solidarity\Transaction\Filter;

use Doctrine\ORM\EntityManagerInterface;
use Laminas\Filter\ToInt;
use Skeletor\Core\Filter\FilterInterface;
use Skeletor\User\Service\Session;
use Volnix\CSRF\CSRF;
use Laminas\I18n\Filter\Alnum;
use Skeletor\Core\Validator\ValidatorException;
class Transaction implements FilterInterface
{

    public function __construct(private \Solidarity\Transaction\Validator\Transaction $validator)
    {
    }

    public function getErrors()
    {
        return $this->validator->getMessages();
    }

    public function filter($postData): array
    {
        $alnum = new Alnum(true);
        $int = new ToInt();

        $beneficiaryType = \Solidarity\Transaction\Entity\Transaction::BENEFICIARY_TYPE_EDUCATOR;
        $beneficiaryId = $postData['educator'];
        if ($postData['educator'] === '' && $postData['beneficiary'] !== '') {
            $beneficiaryType = \Solidarity\Transaction\Entity\Transaction::BENEFICIARY_TYPE_BENEFICIARY;
            $beneficiaryId = $postData['beneficiary'];
        }

        $data = [
            'id' => (isset($postData['id'])) ? $int->filter($postData['id']) : null,
            'beneficiaryId' => $beneficiaryId,
            'beneficiaryType' => $beneficiaryType,
            'project' => $postData['project'],
            'amount' => $postData['amount'],
            'comment' => $postData['comment'],
            'status' => $postData['status'],
            'educator' => $postData['educator'],
            'donor' => $postData['donor'],
            'donorConfirmed' => $postData['donorConfirmed'],
//            'round' => $postData['round'] ?? 1,
            CSRF::TOKEN_NAME => $postData[CSRF::TOKEN_NAME],
        ];
        if (!$this->validator->isValid($data)) {
            throw new ValidatorException();
        }
        unset($data[CSRF::TOKEN_NAME]);

        return $data;
    }

}