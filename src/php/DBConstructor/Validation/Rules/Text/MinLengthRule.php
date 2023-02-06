<?php

declare(strict_types=1);

namespace DBConstructor\Validation\Rules\Text;

use DBConstructor\Validation\Rules\Rule;

class MinLengthRule extends Rule
{
    /** @var int */
    public $minLength;

    public function __construct(int $minLength)
    {
        $this->minLength = $minLength;

        if ($minLength == 1) {
            $this->description = "Wenigstens ein Zeichen lang";
        } else {
            $this->description = "Wenigstens $minLength Zeichen lang";
        }
    }

    public function validate($value = null)
    {
        if ($value !== null) {
            $this->setResult(strlen($value) >= $this->minLength);
        }
    }
}
