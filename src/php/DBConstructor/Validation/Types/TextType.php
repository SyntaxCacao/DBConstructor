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
    const FIELD_INPUT = "input";

    const FIELD_TEXTAREA_LARGE = "textarea_large";

    const FIELD_TEXTAREA_SMALL = "textarea_small";

    const MARKDOWN_DISABLED = "disabled";

    const MARKDOWN_ENABLED_EXPORT_HTML = "enabled_html";

    const MARKDOWN_ENABLED_EXPORT_MD = "enabled_md";

    /** @var string */
    public $fieldType = TextType::FIELD_INPUT;

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

    public function toHTML(): string
    {
        $html = "<p><span class='descriptor'>Data type:</span> Text</p>";
        $html .= "<p><span class='descriptor'>Nullable:</span> ".($this->nullable ? "True" : "False")."</p>";

        if (isset($this->minLength)) {
            $html .= "<p><span class='descriptor'>Minimum length:</span> ".$this->minLength." character(s)</p>";
        }

        if (isset($this->maxLength)) {
            $html .= "<p><span class='descriptor'>Maximum length:</span> ".$this->maxLength." character(s)</p>";
        }

        if (isset($this->regEx)) {
            $html .= "<p><span class='descriptor'>Conforms to regular expression:</span> <code>".htmlspecialchars($this->regEx)."</code></p>";
        }

        return $html;
    }
}
