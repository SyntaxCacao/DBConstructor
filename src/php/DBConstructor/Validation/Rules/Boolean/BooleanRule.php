<?php

declare(strict_types=1);

namespace DBConstructor\Validation\Rules\Boolean;

use DBConstructor\Validation\Rules\Rule;
use DBConstructor\Validation\Types\BooleanType;

class BooleanRule extends Rule
{
    public function __construct()
    {
        $this->description = "Ist ein boolscher Wert (wahr/falsch)";
    }

    public function validate($value = null)
    {
        if ($value !== null) {
            $this->setResult($value === BooleanType::VALUE_FALSE || $value === BooleanType::VALUE_TRUE);
        }
    }
}
