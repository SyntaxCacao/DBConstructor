<?php

declare(strict_types=1);

namespace DBConstructor\Validation\Rules\Decimal;

use DBConstructor\Validation\Rules\Rule;

class DecimalDigitsRule extends Rule
{
    /** @var int */
    public $decimalDigits;

    public function __construct(int $decimalDigits, int $depends)
    {
        $this->decimalDigits = $decimalDigits;
        $this->depends[] = $depends;
        $this->description = "Höchstens $decimalDigits Nachkommastellen";
    }

    public function validate(string $value = null)
    {
        if ($value !== null) {
            $result = preg_match("/^(0|-?[1-9]+[0-9]*)(?:\.([0-9]*[1-9]+))?$/D", $value, $matches);
            $this->setResult($result === 1 && (! isset($matches[2]) || strlen($matches[2]) <= $this->decimalDigits));
        }
    }
}
