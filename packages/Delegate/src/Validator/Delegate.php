<?php

namespace Solidarity\Delegate\Validator;

use Laminas\Validator\EmailAddress;
use Skeletor\Core\Validator\ValidatorInterface;
use Volnix\CSRF\CSRF;

/**
 * Class Client.
 * User validator.
 *
 * @package Fakture\Client\Validator
 */
class Delegate implements ValidatorInterface
{

    /**
     * @var CSRF
     */
    private $csrf;

    private $delegateRepository;

    private $messages = [];

    /**
     * User constructor.
     *
     * @param CSRF $csrf
     */
    public function __construct(CSRF $csrf, \Solidarity\Delegate\Repository\DelegateRepository $delegateRepository)
    {
        $this->csrf               = $csrf;
        $this->delegateRepository = $delegateRepository;
    }

    /**
     * Validates provided data, and sets errors with Flash in session.
     *
     * @param $data
     *
     * @return bool
     */
    public function isValid(array $data): bool
    {
        $valid = true;
        if ($data['email'] && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $this->messages['general'][] = 'Uneta email adresa nije ispravna.' . $data['email'];
            $valid = false;
        }

        if (!empty($data['school'])) {
            $criteria = ['school' => $data['school']];
            $existingDelegates = $this->delegateRepository->fetchAll($criteria);
            foreach ($existingDelegates as $existing) {
                if (isset($data['id']) && $existing->getId() === (int) $data['id']) {
                    continue;
                }
                $this->messages['school'][] = 'Mesto delegata za ovu školu je već zauzeto.';
                $valid = false;
                break;
            }
        }

        if (!$this->csrf->validate($data)) {
            $this->messages['general'][] = 'Stranica je istekla, probajte ponovo.';
            $valid = false;
        }

        return $valid;
    }

    /**
     * Hack used for testing
     *
     * @return string
     */
    public function getMessages(): array
    {
        return $this->messages;
    }
}
