<?php

declare(strict_types=1);

namespace DBConstructor\Forms\Fields;

class SelectField extends GroupableField
{
    /** @var string[] */
    public $options = [];

    public function __construct(string $name, string $label = null)
    {
        parent::__construct($name, $label);
    }

    public function addOption(string $value, string $label)
    {
        $this->options[$value] = $label;
    }

    public function addOptions(array $options)
    {
        foreach ($options as $value => $label) {
            $this->addOption($value, $label);
        }
    }

    public function addOptionsObjects(array $objects, string $valueKey, string $labelKey)
    {
        foreach ($objects as $object) {
            $this->addOption($object->$valueKey, $object->$labelKey);
        }
    }

    public function generateField(): string
    {
        $html = '<select class="form-select" name="field-'.htmlentities($this->name).'"';

        if (isset($this->dependsOn)) {
            $html .= ' data-depends-on="'.$this->dependsOn.'" data-depends-on-value="'.$this->dependsOnValue.'"';
        }

        if ($this->required && ! isset($this->dependsOn)) {
            $html .= ' required';
        }

        if ($this->disabled) {
            $html .= ' disabled';
        }

        $html .= '>';

        foreach ($this->options as $value => $label) {
            $html .= '<option';

            if (! is_null($value)) {
                // TODO Why is this necessary?
                // Exception thrown with mainpage defaultValue in ProjectSettingsForm, $value somehow becomes int
                if (is_int($value)) {
                    $html .= ' value="'.htmlentities((string) $value).'"';
                } else {
                    $html .= ' value="'.htmlentities($value).'"';
                }
            }

            if ($this->hasValue() && $this->value == $value) {
                $html .= ' selected';
            }

            $html .= '>'.htmlentities($label).'</option>';
        }

        $html .= '</select>';

        return $html;
    }

    public function validate(): array
    {
        if ($this->required && ! array_key_exists($this->value, $this->options)) {
            return ["Wählen Sie eine Option."];
        }

        return [];
    }
}
