<?php

declare(strict_types=1);

namespace DBConstructor\Validation;

use DBConstructor\Validation\Rules\Rule;

class Validator
{
    /** @var array<Rule> */
    public $rules = [];

    /**
     * @param Rule $rule
     * @return int key of the inserted $rule in $this->rules array
     */
    public function addRule(Rule $rule): int
    {
        $this->rules[] = $rule;
        return count($this->rules) - 1;
    }

    /**
     * @param string|array $value value to be validated (TODO: set type to string|array when upgrading to PHP 8.0)
     * @return bool true if no rules return invalid
     */
    public function validate($value = null): bool
    {
        $success = true;
        $notDependable = [];

        foreach ($this->rules as $key => $rule) {
            // Resetting result before validating
            //
            // When the same Validator object is used for validating multiple values after another
            // and the result is NOT changed during $rule->validate(), which may happen if the rule
            // is skipped, then the previous validation result would be retained! To fix this, the
            // rule's result gets reset to SKIPPED before every validation
            $rule->result = Rule::RESULT_SKIPPED;

            if (count(array_intersect($notDependable, $rule->depends)) > 0) {
                $notDependable[] = $key;
                continue;
            }

            $rule->validate($value);

            if ($rule->result != Rule::RESULT_VALID) {
                $notDependable[] = $key;
            }

            if ($rule->result == Rule::RESULT_INVALID) {
                $success = false;
            }
        }

        return $success;
    }
}
