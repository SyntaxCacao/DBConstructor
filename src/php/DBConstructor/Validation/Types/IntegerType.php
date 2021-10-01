<?php

declare(strict_types=1);

namespace DBConstructor\Validation\Types;

use DBConstructor\Validation\Rules\Integer\IntegerRule;
use DBConstructor\Validation\Rules\Integer\MaxDigitsRule;
use DBConstructor\Validation\Rules\Integer\MaxValueRule;
use DBConstructor\Validation\Rules\Integer\MinDigitsRule;
use DBConstructor\Validation\Rules\Integer\MinValueRule;
use DBConstructor\Validation\Rules\NotNullRule;
use DBConstructor\Validation\Rules\RegExRule;
use DBConstructor\Validation\Validator;

class IntegerType extends Type
{
    /** @var int */
    public $maxDigits;

    /** @var int */
    public $maxValue;

    /** @var int */
    public $minDigits;

    /** @var int */
    public $minValue;

    /** @var string */
    public $regEx;

    public function buildValidator(): Validator
    {
        $validator = new Validator();

        // nullable
        if (! $this->nullable) {
            $validator->addRule(new NotNullRule());
        }

        // type
        $typeRuleKey = $validator->addRule(new IntegerRule());

        // minDigits
        if (isset($this->minDigits)) {
            $validator->addRule(new MinDigitsRule($this->minDigits, $typeRuleKey));
        }

        // maxDigits
        if (isset($this->maxDigits)) {
            $validator->addRule(new MaxDigitsRule($this->maxDigits, $typeRuleKey));
        }

        // minValue
        if (isset($this->minValue)) {
            $validator->addRule(new MinValueRule($this->minValue, $typeRuleKey));
        }

        // maxValue
        if (isset($this->maxValue)) {
            $validator->addRule(new MaxValueRule($this->maxValue, $typeRuleKey));
        }

        // regEx
        if (isset($this->regEx)) {
            $validator->addRule(new RegExRule($this->regEx));
        }

        return $validator;
    }
}
