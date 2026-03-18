<?php

namespace Solidarity\Beneficiary\Validator;

use Skeletor\Core\Validator\ValidatorInterface;

class Beneficiary implements ValidatorInterface
{
    private array $messages = [];

    public function isValid(array $data): bool
    {
        $this->messages = [];

        if (empty($data['name'])) {
            $this->messages['name'][] = 'Name is required.';
        }

        if (empty($data['accountNumber'])) {
            $this->messages['accountNumber'][] = 'Account number is required.';
        }

        if (empty($data['registeredPeriods'])) {
            $this->messages['registeredPeriods'][] = 'At least one registered period is required.';
        } else {
            foreach ($data['registeredPeriods'] as $index => $row) {
                if (empty($row['period'])) {
                    $this->messages['registeredPeriods'][] = sprintf('Period is required for row %d.', $index + 1);
                }
                if (!isset($row['amount']) || $row['amount'] <= 0) {
                    $this->messages['registeredPeriods'][] = sprintf('Amount must be greater than zero for row %d.', $index + 1);
                }
            }
        }

        return empty($this->messages);
    }

    public function getMessages(): array
    {
        return $this->messages;
    }
}
