<?php

declare(strict_types=1);

namespace DBConstructor\Forms\Fields;

/**
 * Disabled SelectFields always need a defaultValue
 */
class SelectField extends GroupableField
{
    /** @var array<string, string> */
    public $options = [];

    /** @var string|null */
    public $nullLabel;

    public function __construct(string $name, string $label = null, string $nullLabel = null)
    {
        parent::__construct($name, $label);
        $this->nullLabel = $nullLabel;
    }

    public function addOption(string $value, string $label)
    {
        $this->options[$value] = $label;
    }

    /**
     * @param array<mixed, string> $options
     */
    public function addOptions(array $options)
    {
        foreach ($options as $value => $label) {
            $this->addOption((string) $value, $label);
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

        if (! $this->required) {
            if (isset($this->nullLabel)) {
                $html .= '<option value="">'.htmlentities($this->nullLabel).'</option>';
            } else {
                $html .= '<option value="">Keine Auswahl</option>';
            }
        }

        foreach ($this->options as $value => $label) {
            $html .= '<option';

            if (! is_null($value)) {
                // TODO Why is this necessary?
                // Exception thrown with mainpage defaultValue in ProjectSettingsForm, $value somehow becomes int
                // => $value, which was a key in an array, needs to be cast to string as PHP stores
                // numeric keys as ints even if they were put in the array as strings
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
        if (($this->value === null && $this->required) || ! array_key_exists($this->value, $this->options)) {
            return ["Wählen Sie eine Option."];
        }

        return [];
    }
}
