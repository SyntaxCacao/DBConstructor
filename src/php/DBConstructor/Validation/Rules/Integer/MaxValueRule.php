<?php

declare(strict_types=1);

namespace DBConstructor\Validation\Rules\Integer;

use DBConstructor\Validation\Rules\Rule;

class MaxValueRule extends Rule
{
    /** @var int */
    public $maxValue;

    public function __construct(int $maxValue, int $depends)
    {
        $this->depends[] = $depends;
        $this->description = "Höchstwert: $maxValue";
        $this->maxValue = $maxValue;
    }

    public function validate($value = null)
    {
        if ($value !== null) {
            $this->setResult(intval($value) <= $this->maxValue);
        }
    }
}
