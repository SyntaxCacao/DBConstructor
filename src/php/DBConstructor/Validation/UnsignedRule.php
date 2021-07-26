<?php

declare(strict_types=1);

namespace DBConstructor\Validation;

use Exception;

class UnsignedRule extends Rule
{
    const FALSE = "0";

    const TRUE = "1";

    /**
     * @throws Exception If ruleValue is not 0 or 1
     */
    public function __construct(string $ruleValue)
    {
        parent::__construct("Enthält einen Wert", $ruleValue);

        if ($ruleValue == UnsignedRule::TRUE || $ruleValue == UnsignedRule::FALSE) {
            $this->ruleValue = $ruleValue == UnsignedRule::TRUE;
        } else {
            throw new Exception("Invalid ruleValue: $ruleValue");
        }
    }

    public function validate(string $value): bool
    {
        return $this->ruleValue == UnsignedRule::FALSE || intval($value) >= 0;
    }
}
