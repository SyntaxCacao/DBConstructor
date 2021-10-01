<?php

declare(strict_types=1);

namespace DBConstructor\Validation\Types;

use DBConstructor\Validation\Rules\NotNullRule;
use DBConstructor\Validation\Rules\RegExRule;
use DBConstructor\Validation\Rules\Text\MaxLengthRule;
use DBConstructor\Validation\Rules\Text\MinLengthRule;
use DBConstructor\Validation\Validator;

class TextType extends Type
{
    const FIELD_INPUT_BLOCK = "input_block";

    const FIELD_INPUT_DEFAULT = "input_default";

    const FIELD_TEXTAREA_LARGE = "textarea_large";

    const FIELD_TEXTAREA_SMALL = "textarea_small";

    const MARKDOWN_DISABLED = "disabled";

    const MARKDOWN_ENABLED_EXPORT_HTML = "enabled_html";

    const MARKDOWN_ENABLED_EXPORT_MD = "enabled_md";

    /** @var string */
    public $fieldType = TextType::FIELD_INPUT_DEFAULT;

    /** @var string */
    public $markdown = TextType::MARKDOWN_DISABLED;

    /** @var int */
    public $maxLength;

    /** @var int */
    public $minLength;

    /** @var string */
    public $regEx;

    public function buildValidator(): Validator
    {
        $validator = new Validator();

        // nullable
        if (! $this->nullable) {
            $validator->addRule(new NotNullRule());
        }

        // minLength
        if (isset($this->minLength)) {
            $validator->addRule(new MinLengthRule($this->minLength));
        }

        // maxLength
        if (isset($this->maxLength)) {
            $validator->addRule(new MaxLengthRule($this->maxLength));
        }

        // regEx
        if (isset($this->regEx)) {
            $validator->addRule(new RegExRule($this->regEx));
        }

        return $validator;
    }
}
