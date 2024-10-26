<?php

declare(strict_types=1);

namespace DBConstructor\Validation\Types;

use DBConstructor\Validation\Rules\Date\DateRule;
use DBConstructor\Validation\Rules\NotNullRule;
use DBConstructor\Validation\Validator;

class DateType extends Type
{
    public function buildValidator(): Validator
    {
        $validator = new Validator();

        // nullable
        if (! $this->nullable) {
            $validator->addRule(new NotNullRule());
        }

        // type
        $validator->addRule(new DateRule());

        return $validator;
    }

    public function toHTML(): string
    {
        $html = "<p><span class='descriptor'>Data type:</span> Date</p>";
        $html .= "<p><span class='descriptor'>Nullable:</span> ".($this->nullable ? "True" : "False")."</p>";

        return $html;
    }
}
