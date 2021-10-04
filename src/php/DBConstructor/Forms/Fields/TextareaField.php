<?php

declare(strict_types=1);

namespace DBConstructor\Forms\Fields;

class TextareaField extends GroupableField
{
    /** @var string|null */
    public $id;

    /** @var bool */
    public $larger = false;

    /** @var int|null */
    public $maxLength;

    /** @var int|null */
    public $minLength;

    /** @var bool */
    public $monospace = false;

    /** @var string|null */
    public $placeholder;

    public function __construct(string $name, string $label = null)
    {
        parent::__construct($name, $label);
    }

    public function generateField(): string
    {
        $html = '<textarea class="form-textarea';

        if ($this->larger) {
            $html .= " form-textarea-larger";
        }

        if ($this->monospace) {
            $html .= " form-textarea-monospace";
        }

        $html .= '" name="field-'.htmlentities($this->name).'"';

        if (isset($this->id)) {
            $html .= ' id="'.htmlentities($this->id).'"';
        }

        if (isset($this->placeholder)) {
            $html .= ' placeholder="'.htmlentities($this->placeholder).'"';
        }

        if (isset($this->dependsOn)) {
            $html .= ' data-depends-on="'.$this->dependsOn.'" data-depends-on-value="'.$this->dependsOnValue.'"';
        }

        if (isset($this->maxLength)) {
            $html .= ' maxlength="'.$this->maxLength.'"';
        }

        if (isset($this->minLength)) {
            $html .= ' minlength="'.$this->minLength.'"';
        }

        if ($this->required && ! isset($this->dependsOn)) {
            $html .= ' required';
        }

        if ($this->disabled) {
            $html .= ' disabled';
        }

        $html .= '>';

        if ($this->hasValue()) {
            $html .= htmlentities($this->value);
        }

        $html .= "</textarea>";
        return $html;
    }

    public function validate(): array
    {
        $issues = [];

        if (! is_string($this->value)) {
            $issues[] = "Geben Sie eine Zeichenkette ein.";
            return $issues;
        }

        if (isset($this->maxLength) && strlen($this->value) > $this->maxLength) {
            $issues[] = "Geben Sie höchstens $this->maxLength Zeichen ein.";
        }

        if (isset($this->minLength) && strlen($this->value) < $this->minLength) {
            $issues[] = "Geben Sie wenigstens $this->minLength Zeichen ein.";
        }

        $this->callClosures($issues);

        return $issues;
    }
}
