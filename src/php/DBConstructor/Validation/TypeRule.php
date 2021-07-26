<?php

declare(strict_types=1);

namespace DBConstructor\Validation;

use DBConstructor\Models\TextualColumn;
use Exception;

class TypeRule extends Rule
{
    /**
     * @throws Exception If ruleValue is not 0 or 1
     */
    public function __construct(string $ruleValue)
    {
        parent::__construct("Entspricht dem Datentyp", $ruleValue);

        if (! array_key_exists($ruleValue, TextualColumn::TYPES)) {
            throw new Exception("Invalid ruleValue: $ruleValue");
        }
    }

    /**
     * @throws Exception
     */
    public function validate(string $value): bool
    {
        if ($this->ruleValue == TextualColumn::TYPE_TEXT) {
            return true;
        } else if (TextualColumn::TYPE_INTEGER) {
            return ctype_digit($value);
        } else {
            throw new Exception("Unknown type $this->ruleValue");
        }
    }
}
