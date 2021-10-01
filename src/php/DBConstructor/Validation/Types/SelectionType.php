<?php

declare(strict_types=1);

namespace DBConstructor\Validation\Types;

use DBConstructor\Validation\Rules\Selection\OptionRule;
use DBConstructor\Validation\Rules\NotNullRule;
use DBConstructor\Validation\Validator;

class SelectionType extends Type
{
    const INTERNAL_SEPARATOR = SelectionType::SEPARATOR_SPACE;

    const SEPARATOR_COMMA = ",";

    const SEPARATOR_SEMICOLON = ";";

    const SEPARATOR_SPACE = " ";

    /** @var bool */
    public $allowMultiple = false;

    /**
     * keys = names, values = labels
     *
     * @var string[]
     */
    public $options;

    /** @var string */
    public $separator = SelectionType::SEPARATOR_SPACE;

    public function buildValidator(): Validator
    {
        $validator = new Validator();

        // nullable
        if (! $this->nullable) {
            $validator->addRule(new NotNullRule());
        }

        // option
        $validator->addRule(new OptionRule(array_keys($this->options), $this->allowMultiple));

        return $validator;
    }
}
