<?php

declare(strict_types=1);

namespace DBConstructor\Validation;

use Exception;
use ReflectionClass;

class Validator
{
    /**
     * @throws Exception
     */
    public static function createIntegerValidator(bool $notNull = null, bool $unsigned = null, string $minValue = null, string $maxValue = null): Validator
    {
        $validator = new Validator();

        if (! is_null($notNull) && $notNull) {
            $validator->rules[] = new NotNullRule(NotNullRule::TRUE);
        }

        if (! is_null($unsigned) && $unsigned) {
            $validator->rules[] = new UnsignedRule(UnsignedRule::TRUE);
        }

        if (! is_null($minValue)) {
            $validator->rules[] = new MinValueRule($minValue);
        }

        if (! is_null($maxValue)) {
            $validator->rules[] = new MaxValueRule($maxValue);
        }

        return $validator;
    }

    public static function createRelationValidator(bool $notNull = null): Validator
    {
        $validator = new Validator();

        if (! is_null($notNull) && $notNull) {
            $validator->rules[] = new NotNullRule(NotNullRule::TRUE);
        }

        $validator->rules[] = new TargetRowExistsRule(TargetRowExistsRule::TRUE);

        return $validator;
    }

    /**
     * @throws Exception
     */
    public static function createTextValidator(bool $notNull = null, string $minLength = null, string $maxLength = null, string $regex = null): Validator
    {
        $validator = new Validator();

        if (! is_null($notNull) && $notNull) {
            $validator->rules[] = new NotNullRule(NotNullRule::TRUE);
        }

        if (! is_null($minLength)) {
            $validator->rules[] = new MinLengthRule($minLength);
        }

        if (! is_null($maxLength)) {
            $validator->rules[] = new MaxLengthRule($maxLength);
        }

        if (! is_null($regex)) {
            $validator->rules[] = new RegexRule($regex);
        }

        return $validator;
    }

    /**
     * @param string|null $type null for relational columns
     * @throws Exception
     */
    public static function fromJSON(string $json, string $type = null): Validator
    {
        $validator = new Validator();
        $rules = json_decode($json);

        if ($rules === false) {
            throw new Exception("Could not encode from JSON");
        }

        if (! is_null($type)) {
            $validator->type = $type;
        }

        foreach ($rules as $key => $value) {
            if (array_key_exists($key, Rule::RULES)) {
                $validator->rules[] = (new ReflectionClass(Rule::RULES[$key]))->newInstance($value);
            } else {
                throw new Exception("Could not find rule for key $key");
            }
        }

        return $validator;
    }

    /** @var Rule[]; */
    public $rules = [];

    /** @var string */
    public $type;

    /**
     * @throws Exception
     */
    public function toJSON(): string
    {
        $rules = [];

        foreach ($this->rules as $rule) {
            $rules[$rule->getName()] = $rule->ruleValue;
        }

        $json = json_encode($rules);

        if ($json === false) {
            throw new Exception("Could not encode to JSON");
        }

        return $json;
    }

    /**
     * @param $value string|null For relational fields, a check if target row exists beforehand and if
     * target row does not exist an empty string must be inserted as value instead of the actual row id
     * @throws Exception
     */
    public function validate(string $value = null): ValidationResult
    {
        $result = new ValidationResult();
        $validType = true;

        if (isset($this->type) && ! is_null(/*$this->*/$value)) {
            // type is not set for relational columns
            $typeRule = new TypeRule($this->type);
            $validType = $typeRule->validate($value);

            if (! $validType) {
                $result->valid = false;
                $result->failed[] = $typeRule;
            }
        }

        foreach ($this->rules as $rule) {
            if ((! is_null($value) || $rule->acceptNull) && ($validType || $rule->acceptInvalidType)) {
                $ruleResult = $rule->validate($value);
                if (! $ruleResult) {
                    $result->valid = false;
                    $result->failed[] = $rule;
                }
            }
        }

        return $result;
    }
}
