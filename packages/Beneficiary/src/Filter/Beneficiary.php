<?php

namespace Solidarity\Beneficiary\Filter;

use Skeletor\Core\Filter\FilterInterface;
use Solidarity\Beneficiary\Validator\Beneficiary as BeneficiaryValidator;

class Beneficiary implements FilterInterface
{
    public function __construct(
        private BeneficiaryValidator $validator
    ) {
    }

    public function filter($postData): array
    {
        // todo add validation for maxAmount from project if set for registered projects when saving

        //$postData['paymentMethods']
        //$postData['registeredProjects']);
        $data = [
            'name' => trim($postData['name'] ?? ''),
            'accountNumber' => trim($postData['accountNumber'] ?? ''),
            'status' => (int) ($postData['status'] ?? \Solidarity\Beneficiary\Entity\Beneficiary::STATUS_NEW),
            'comment' => trim($postData['comment'] ?? ''),
            'school' => $postData['school'] ?? null,
            'createdBy' => $postData['createdBy'] ?? null,
        ];

        // Parse registeredPeriods rows from form
        $registeredPeriods = [];
        if (isset($postData['registeredPeriods']) && is_array($postData['registeredPeriods'])) {
            foreach ($postData['registeredPeriods'] as $row) {
                if (empty($row['period'])) {
                    continue;
                }
                $registeredPeriods[] = [
                    'project' => (int) ($row['project'] ?? 0),
                    'period' => (int) $row['period'],
                    'amount' => (int) ($row['amount'] ?? 0),
                ];
            }
        }
        $data['registeredPeriods'] = $registeredPeriods;

        if (!$this->validator->isValid($data)) {
            throw new \Skeletor\Core\Validator\ValidatorException();
        }

        return $data;
    }

    public function getErrors()
    {
        return $this->validator->getMessages();
    }
}
