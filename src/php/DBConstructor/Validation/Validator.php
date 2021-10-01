<?php

declare(strict_types=1);

namespace DBConstructor\Validation;

use DBConstructor\Validation\Rules\Rule;

class Validator
{
    /** @var Rule[] */
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
     * @param string $value string to be validated
     * @return bool true if no rules return invalid
     */
    public function validate(string $value = null): bool
    {
        $success = true;
        $notDependable = [];

        foreach ($this->rules as $key => $rule) {
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
