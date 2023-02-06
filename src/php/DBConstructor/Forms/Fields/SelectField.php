<?php

declare(strict_types=1);

namespace DBConstructor\Forms\Fields;

/**
 * {@link $value} will be an array, if {@link $allowMultiple} is {@code true}, a string otherwise.
 * If {@link $allowMultiple} is {@code true}, {@code []} will be added to the name attribute,
 * so that PHP will automatically put the selected options in an array.
 *
 * Disabled {@link SelectFields} always need a {@link $defaultValue}.
 */
class SelectField extends GroupableField
{
    /** @var bool */
    public $allowMultiple = false;

    /**
     * Controls automatic adjustment of size attribute if {@link $allowMultiple} is {@code true}.
     * Size will equal number of options given, limited by {@code maxSize}.
     *
     * @var int
     */
    public $maxSize = 12;

    /** @var string|null */
    public $nullLabel;

    /** @var array<string, string> */
    public $options = [];

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
        $html = '<select class="form-select';

        if ($this->allowMultiple && count($this->options) <= $this->maxSize) {
            $html .= ' form-select-multiple-noscroll';
        }

        $html .= '" name="field-'.htmlentities($this->name);

        if ($this->allowMultiple) {
            $html .= '[]';
        }

        $html .= '"';

        if ($this->allowMultiple) {
            $html .= ' size="'.min(count($this->options), $this->maxSize).'"';
        }

        if (isset($this->dependsOn)) {
            $html .= ' data-depends-on="'.$this->dependsOn.'" data-depends-on-value="'.$this->dependsOnValue.'"';
        }

        if ($this->allowMultiple) {
            $html .= ' multiple';
        }

        if ($this->required && ! isset($this->dependsOn)) {
            $html .= ' required';
        }

        if ($this->disabled) {
            $html .= ' disabled';
        }

        $html .= '>';

        if (! $this->required && ! $this->allowMultiple) {
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

            if ($this->hasValue() && (
                    (! $this->allowMultiple && $this->value == $value) ||
                    ($this->allowMultiple && in_array($value, $this->value)))) {
                $html .= ' selected';
            }

            $html .= '>'.htmlentities($label).'</option>';
        }

        $html .= '</select>';

        if ($this->allowMultiple) {
            // not ideal to do this here (bypassing $footer), but ::generateGroupable() won't be called in RowForm
            $html .= '<p class="form-footer">Zum Auswählen mehrerer Optionen beim Anklicken <kbd>Strg</kbd> drücken.</p>';
        }

        return $html;
    }

    public function insertValue($value)
    {
        if ($this->allowMultiple && empty($value)) {
            $value = null;
        }

        parent::insertValue($value);
    }

    public function validate(): array
    {
        if ($this->value === null) {
            if ($this->required) {
                return ["Wählen Sie eine Option."];
            }
        } else {
            if ($this->allowMultiple) {
                foreach ($this->value as $option) {
                    if (! array_key_exists($option, $this->options)) {
                        return ["Wählen Sie eine gültige Option."];
                    }
                }
            } else {
                if (! array_key_exists($this->value, $this->options)) {
                    return ["Wählen Sie eine gültige Option."];
                }
            }
        }

        return [];
    }
}
