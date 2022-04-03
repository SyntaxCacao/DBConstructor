<?php

declare(strict_types=1);

namespace DBConstructor\Validation\Rules\Integer;

use DBConstructor\Validation\Rules\Rule;

class IntegerRule extends Rule
{
    public function __construct()
    {
        $this->description = "Ist eine ganze Zahl";
    }

    public function validate(string $value = null)
    {
        if ($value !== null) {
            // regEx: value may have a sign, value may not have leading zeros
            $this->setResult(preg_match("/^(?:0|-?[1-9]+[0-9]*)$/D", $value) === 1);
        }
    }
}
