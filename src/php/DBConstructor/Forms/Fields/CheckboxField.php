<?php

declare(strict_types=1);

namespace DBConstructor\Forms\Fields;

class CheckboxField extends Field
{
    const VALUE = "checked";

    public $dependsPadding = true;

    public $labelBold = true;

    public function __construct(string $name, string $label = null)
    {
        parent::__construct($name, $label);
        $this->required = false;
        $this->value = false;
    }

    public function generateField(): string
    {
        $html = '<input class="form-checkbox" name="field-'.htmlentities($this->name).'" type="checkbox" value="'.CheckboxField::VALUE.'"';

        if (isset($this->dependsOn)) {
            $html .= ' data-depends-on="'.$this->dependsOn.'" data-depends-on-value="'.$this->dependsOnValue.'"';
        }

        if ($this->value == true) {
            $html .= " checked";
        }

        if ($this->required && ! isset($this->dependsOn)) {
            $html .= " required";
        }

        if ($this->disabled) {
            $html .= " disabled";
        }

        $html .= ">";

        return $html;
    }

    /**
     * @param array<string> $errorMessages
     */
    public function generateGroup(array $errorMessages = []): string
    {
        $html = '<label class="form-checkbox-group';

        if (isset($this->dependsOn)) {
            $html .= ' form-group-depend';

            if (! $this->dependsPadding) {
                $html .= ' form-group-depend-no-padding';
            }
        }

        $html .= '">'.$this->generateField().'<div class="form-checkbox-label"><p class="form-label';

        if (! $this->labelBold) {
            $html .= ' form-label-weight-normal';
        }

        $html .= '">'.htmlentities($this->label).'</p>';

        // TODO: malformated atm
        if ($this->required) {
            $html .= '<span class="form-label-addition"> (erforderlich)</span>';
        }

        if (isset($this->description)) {
            $html .= '<p class="form-group-description">'.htmlentities($this->description).'</p>';
        }

        $html .= '</div></label>';

        return $html;
    }

    public function hasValue(): bool
    {
        return true;
    }

    public function insertValue($value)
    {
        if ($value == CheckboxField::VALUE) {
            $this->value = true;
        } else if (is_bool($value)) {
            $this->value = $value;
        } else {
            $this->value = false;
        }
    }

    public function validate(): array
    {
        if ($this->required && $this->value == false) {
            return ["Dieser Haken muss gesetzt werden."];
        }

        return [];
    }
}
