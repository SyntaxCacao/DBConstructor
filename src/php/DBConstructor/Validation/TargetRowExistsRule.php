<?php

declare(strict_types=1);

namespace DBConstructor\Validation;

use Exception;

class TargetRowExistsRule extends Rule
{
    const FALSE = "0";

    const TRUE = "1";

    /**
     * @throws Exception
     */
    public function __construct(string $ruleValue)
    {
        parent::__construct("Referenzierte Zeile existiert", $ruleValue);

        if ($ruleValue == TargetRowExistsRule::TRUE || $ruleValue == TargetRowExistsRule::FALSE) {
            $this->ruleValue = $ruleValue == TargetRowExistsRule::TRUE;
        } else {
            throw new Exception("Invalid ruleValue: $ruleValue");
        }
    }

    public function validate($value): bool
    {
        // Target row check must be performed before validating, if target row
        // does not exist an empty string must be inserted as value instead of
        // the actual row id
        return $value != "";
    }
}
