<?php

declare(strict_types=1);

namespace DBConstructor\Validation\Rules\Decimal;

use DBConstructor\Validation\Rules\Rule;

class DecimalRule extends Rule
{
    public function __construct()
    {
        $this->description = "Ist eine Dezimalzahl<br>(Dezimaltrennzeichen: Punkt)";
    }

    public function validate(string $value = null)
    {
        if ($value !== null) {
            $this->setResult(preg_match("/^(?:0|-?[1-9]+[0-9]*)(?:\.[0-9]*[1-9]+)?$/", $value) === 1);
        }
    }
}
