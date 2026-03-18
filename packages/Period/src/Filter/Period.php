<?php

namespace Solidarity\Period\Filter;

use Doctrine\ORM\EntityManagerInterface;
use Laminas\Filter\ToInt;
use Skeletor\Core\Filter\FilterInterface;
use Volnix\CSRF\CSRF;
use Laminas\I18n\Filter\Alnum;
use Skeletor\Core\Validator\ValidatorException;
class Period implements FilterInterface
{

//    public function __construct(private \Solidarity\Period\Validator\Period $validator)
    public function __construct()
    {
    }

    public function getErrors()
    {
        return [];
//        return $this->validator->getMessages();
    }

    public function filter($postData): array
    {
        $alnum = new Alnum(true);
        $int = new ToInt();

        $data = [
            'id' => (isset($postData['id'])) ? $int->filter($postData['id']) : null,
            'month' => $postData['month'],
            'year' => $postData['year'],
            'type' => $postData['type'],
            'active' => $postData['active'],
            'project' => $postData['project'],
            'processing' => $postData['processing'],
            CSRF::TOKEN_NAME => $postData[CSRF::TOKEN_NAME],
        ];
//        if (!$this->validator->isValid($data)) {
//            throw new ValidatorException();
//        }
        unset($data[CSRF::TOKEN_NAME]);

        return $data;
    }

}