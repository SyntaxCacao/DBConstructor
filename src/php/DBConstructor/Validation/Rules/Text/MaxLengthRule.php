<?php

declare(strict_types=1);

namespace DBConstructor\Validation\Rules\Text;

use DBConstructor\Validation\Rules\Rule;

class MaxLengthRule extends Rule
{
    /** @var int */
    public $maxLength;

    public function __construct(int $maxLength)
    {
        $this->maxLength = $maxLength;

        if ($maxLength == 1) {
            $this->description = "Höchstens ein Zeichen lang";
        } else {
            $this->description = "Höchstens $maxLength Zeichen lang";
        }
    }

    public function validate($value = null)
    {
        if ($value !== null) {
            $this->setResult(mb_strlen($value) <= $this->maxLength);
        }
    }
}
