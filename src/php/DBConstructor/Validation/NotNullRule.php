<?php

declare(strict_types=1);

namespace DBConstructor\Validation;

use Exception;

class NotNullRule extends Rule
{
    const FALSE = "0";

    const TRUE = "1";

    /**
     * @throws Exception If ruleValue is not 0 or 1
     */
    public function __construct(string $ruleValue)
    {
        parent::__construct("Enthält einen Wert", $ruleValue);
        $this->acceptInvalidType = true;
        $this->acceptNull = true;

        if ($ruleValue == NotNullRule::TRUE || $ruleValue == NotNullRule::FALSE) {
            $this->ruleValue = $ruleValue == NotNullRule::TRUE;
        } else {
            throw new Exception("Invalid ruleValue: $ruleValue");
        }
    }

    public function validate(string $value): bool
    {
        return $this->ruleValue == NotNullRule::FALSE || ! is_null($value);
    }
}
