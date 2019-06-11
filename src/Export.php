<?php

namespace Reaper;

class Export extends AbstractClass
{
    private $registrationNumbers = [];

    public function __construct ()
    {
        parent::__construct();
    }

    public function export ()
    {
        print_r($this->getRegistrationNumbers());
    }

    private function getRegistrationNumbers ()
    {
        if (empty($this->registrationNumbers)) {
            $this->registrationNumbers = $this->pdo->getRegistrationNumbers();
        }
        return $this->registrationNumbers;
    }

}