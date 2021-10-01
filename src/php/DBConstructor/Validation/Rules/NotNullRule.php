<?php

declare(strict_types=1);

namespace DBConstructor\Validation\Rules;

class NotNullRule extends Rule
{
    public function __construct()
    {
        $this->description = "Enthält einen Wert";
    }

    public function validate(string $value = null)
    {
        $this->setResult($value !== null);
    }
}
