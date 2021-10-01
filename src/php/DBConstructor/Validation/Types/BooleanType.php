<?php

declare(strict_types=1);

namespace DBConstructor\Validation\Types;

use DBConstructor\Validation\Rules\Boolean\BooleanRule;
use DBConstructor\Validation\Rules\Boolean\ForceTrueRule;
use DBConstructor\Validation\Rules\NotNullRule;
use DBConstructor\Validation\Validator;

class BooleanType extends Type
{
    const VALUE_FALSE = "0";

    const VALUE_TRUE = "1";

    /** @var string|null */
    public $falseLabel;

    /** @var bool */
    public $forceTrue = false;

    /** @var string|null */
    public $trueLabel;

    public function buildValidator(): Validator
    {
        $validator = new Validator();

        // nullable
        if (! $this->nullable) {
            $validator->addRule(new NotNullRule());
        }

        // type
        $typeRuleKey = $validator->addRule(new BooleanRule());

        // forceTrue
        if ($this->forceTrue) {
            $validator->addRule(new ForceTrueRule($typeRuleKey));
        }

        return $validator;
    }
}
