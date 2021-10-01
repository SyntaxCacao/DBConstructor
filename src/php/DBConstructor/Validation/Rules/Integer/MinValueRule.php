<?php

declare(strict_types=1);

namespace DBConstructor\Validation\Rules\Integer;

use DBConstructor\Validation\Rules\Rule;

class MinValueRule extends Rule
{
    /** @var int */
    public $minValue;

    public function __construct(int $minValue, int $depends)
    {
        $this->depends[] = $depends;
        $this->description = "Mindestwert: $minValue";
        $this->minValue = $minValue;
    }

    public function validate(string $value = null)
    {
        if ($value !== null) {
            $this->setResult(intval($value) >= $this->minValue);
        }
    }
}
