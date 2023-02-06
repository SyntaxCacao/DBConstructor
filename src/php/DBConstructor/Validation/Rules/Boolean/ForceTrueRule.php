<?php

declare(strict_types=1);

namespace DBConstructor\Validation\Rules\Boolean;

use DBConstructor\Validation\Rules\Rule;
use DBConstructor\Validation\Types\BooleanType;

class ForceTrueRule extends Rule
{
    public function __construct(int $depends)
    {
        $this->depends[] = $depends;
        $this->description = "Ist wahr";
    }

    public function validate($value = null)
    {
        if ($value !== null) {
            $this->setResult($value === BooleanType::VALUE_TRUE);
        }
    }
}
