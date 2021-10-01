<?php

declare(strict_types=1);

namespace DBConstructor\Validation\Rules\Date;

use DBConstructor\Validation\Rules\Rule;

class DateRule extends Rule
{
    public function __construct()
    {
        $this->description = "Ist eine Datumsangabe im Format YYYY-MM-DD (Jahr 1000-9999)";
    }

    public function validate(string $value = null)
    {
        if ($value !== null) {
            $this->setResult(preg_match("/^[1-9][0-9]{3}-(?:0[1-9]|1[0-2])-(?:0[1-9]|[1-2][0-9]|3[0-1])$/", $value) === 1);
        }
    }
}
