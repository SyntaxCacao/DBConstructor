<?php

declare(strict_types=1);

namespace DBConstructor\Validation\Types;

use DBConstructor\Validation\Rules\Decimal\DecimalDigitsRule;
use DBConstructor\Validation\Rules\Decimal\DecimalRule;
use DBConstructor\Validation\Rules\Decimal\IntegerDigitsRule;
use DBConstructor\Validation\Rules\NotNullRule;
use DBConstructor\Validation\Rules\RegExRule;
use DBConstructor\Validation\Validator;

class DecimalType extends Type
{
    /** @var int */
    public $decimalDigits;

    /** @var int */
    public $integerDigits;

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
        $typeRuleKey = $validator->addRule(new DecimalRule());

        // integerDigits
        $validator->addRule(new IntegerDigitsRule($this->integerDigits, $typeRuleKey));

        // decimalDigits
        $validator->addRule(new DecimalDigitsRule($this->decimalDigits, $typeRuleKey));

        // regEx
        if (isset($this->regEx)) {
            $validator->addRule(new RegExRule($this->regEx));
        }

        return $validator;
    }

    public function toHTML(): string
    {
        $html = "<p><span class='descriptor'>Data type:</span> Decimal</p>";
        $html .= "<p><span class='descriptor'>Nullable:</span> ".($this->nullable ? "True" : "False")."</p>";

        if (isset($this->integerDigits)) {
            $html .= "<p><span class='descriptor'>Pre-decimal places:</span> ".$this->integerDigits."</p>";
        }

        if (isset($this->decimalDigits)) {
            $html .= "<p><span class='descriptor'>Decimal places:</span> ".$this->decimalDigits."</p>";
        }

        if (isset($this->regEx)) {
            $html .= "<p><span class='descriptor'>Conforms to regular expression:</span> <code>".htmlspecialchars($this->regEx)."</code></p>";
        }

        return $html;
    }
}
