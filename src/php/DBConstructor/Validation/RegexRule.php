<?php

declare(strict_types=1);

namespace DBConstructor\Validation;

use Exception;

class RegexRule extends Rule
{
    /**
     * @throws Exception If ruleValue is not an integer
     */
    public function __construct(string $ruleValue)
    {
        parent::__construct("Wird gematcht von /$ruleValue/", $ruleValue);
    }

    public function validate(string $value): bool
    {
        //var_dump($this->ruleValue);exit;
        return preg_match("/".$this->ruleValue."/", $value);
    }
}
