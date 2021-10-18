<?php

declare(strict_types=1);

namespace DBConstructor\Validation\Rules\Decimal;

use DBConstructor\Validation\Rules\Rule;

class IntegerDigitsRule extends Rule
{
    /** @var int */
    public $integerDigits;

    public function __construct(int $integerDigits, int $depends)
    {
        $this->integerDigits = $integerDigits;
        $this->depends[] = $depends;
        $this->description = "Höchstens $integerDigits Vorkommastellen";
    }

    public function validate(string $value = null)
    {
        if ($value !== null) {
            $matches = [];
            $result = preg_match("/^(0|-?[1-9]+[0-9]*)(?:\.([0-9]*[1-9]+))?$/", $value, $matches);
            $this->setResult($result === 1 && strlen(trim($matches[1], "-")) <= $this->integerDigits);
        }
    }
}
