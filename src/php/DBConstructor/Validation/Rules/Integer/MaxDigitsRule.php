<?php

declare(strict_types=1);

namespace DBConstructor\Validation\Rules\Integer;

use DBConstructor\Validation\Rules\Rule;

class MaxDigitsRule extends Rule
{
    /** @var int */
    public $maxDigits;

    public function __construct(int $maxDigits, int $depends)
    {
        $this->depends[] = $depends;
        $this->maxDigits = $maxDigits;

        if ($maxDigits == 1) {
            $this->description = "Höchstens eine Stelle";
        } else {
            $this->description = "Höchstens $maxDigits Stellen";
        }
    }

    public function validate(string $value = null)
    {
        if ($value !== null) {
            $matches = [];
            $result = preg_match("/^(?:0|-?[1-9]+[0-9]*)$/", $value, $matches);
            $this->setResult($result === 1 && strlen(trim($value, "-")) <= $this->maxDigits);
        }
    }
}
