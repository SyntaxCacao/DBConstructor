<?php

declare(strict_types=1);

namespace DBConstructor\Validation;

use Exception;

class MaxLengthRule extends Rule
{
    /**
     * @throws Exception If ruleValue is not an integer
     */
    public function __construct(string $ruleValue)
    {
        parent::__construct("Höchstens $ruleValue Zeichen lang", $ruleValue);

        if (! ctype_digit($this->ruleValue)) {
            throw new Exception("Invalid ruleValue: $ruleValue");
        }
    }

    public function validate(string $value): bool
    {
        return intval($this->ruleValue) >= strlen($value);
    }
}
