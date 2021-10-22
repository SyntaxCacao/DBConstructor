<?php

declare(strict_types=1);

namespace DBConstructor\Validation\Rules;

abstract class Rule
{
    const RESULT_VALID = 0;

    const RESULT_INVALID = 1;

    const RESULT_SKIPPED = 2;

    /**
     * Array of keys of rules in Validator::$rules that need to succeed before
     * this Rule's validate function may be called
     *
     * @var array<int>
     */
    public $depends = [];

    /** @var string */
    public $description;

    /**
     * One of the constants declared in this class
     *
     * @var int
     */
    public $result = Rule::RESULT_SKIPPED;

    /**
     * @param bool $valid sets $result to VALID if true and to INVALID if false
     */
    public function setResult(bool $valid)
    {
        if ($valid) {
            $this->result = Rule::RESULT_VALID;
        } else {
            $this->result = Rule::RESULT_INVALID;
        }
    }

    abstract function validate(string $value);
}
