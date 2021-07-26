<?php

declare(strict_types=1);

namespace DBConstructor\Forms\Fields;

class IntegerField extends GroupableField
{
    /** @var int|null */
    public $maxValue;

    /** @var int|null */
    public $minValue;

    /**
     * @param string|null $label
     */
    public function __construct(string $name, $label = null)
    {
        parent::__construct($name, $label);
    }

    public function generateField(bool $placeholderLabel = false): string
    {
        $html = '<input class="form-input" type="number" name="field-'.htmlentities($this->name).'"';

        if (isset($this->dependsOn)) {
            $html .= ' data-depends-on="'.$this->dependsOn.'" data-depends-on-value="'.$this->dependsOnValue.'"';
        }

        if ($placeholderLabel) {
            $html .= ' placeholder="'.htmlentities($this->label).'"';
        }

        if (isset($this->maxValue)) {
            $html .= ' max="'.$this->maxValue.'"';
        }

        if (isset($this->minValue)) {
            $html .= ' min="'.$this->minValue.'"';
        }

        $html .= ' step="1"';

        if ($this->hasValue()) {
            $html .= ' value="'.htmlentities($this->value).'"';
        }

        if ($this->required && ! isset($this->dependsOn)) {
            $html .= ' required';
        }

        if ($this->disabled) {
            $html .= ' disabled';
        }

        $html .= ">";
        return $html;
    }

    public function validate(): array
    {
        $issues = [];

        if (! ctype_digit($this->value)) {
            // TODO: It is not possible to enter negative integers
            $issues[] = "Geben Sie eine ganze Zahl ein.";
        }

        if (isset($this->maxValue) && intval($this->value) > $this->maxValue) {
            $issues[] = "Geben Sie einen Wert ≤ $this->maxValue ein.";
        }

        if (isset($this->minValue) && intval($this->value) < $this->minValue) {
            $issues[] = "Geben Sie einen Wert ≥ $this->minValue ein.";
        }

        $this->callClosures($issues);

        return $issues;
    }
}
