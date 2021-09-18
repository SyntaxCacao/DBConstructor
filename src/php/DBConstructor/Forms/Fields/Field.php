<?php

declare(strict_types=1);

namespace DBConstructor\Forms\Fields;

abstract class Field
{
    /** @var mixed */
    public $defaultValue;

    /** @var string|null */
    public $dependsOn;

    /** @var string|null */
    public $dependsOnValue;

    /** @var string|null */
    public $description;

    /** @var bool */
    public $disabled = false;

    /** @var string|null */
    public $label;

    /** @var string */
    public $name;

    /** @var bool */
    public $required = true;

    /**
     * TODO: Only implemented in TextField, IntegerField at the moment
     *
     * @var ValidationClosure[];
     */
    public $validationClosures = [];

    /**
     * Use setter!
     *
     * @var string|null
     * @see Field::insertValue()
     */
    public $value;

    public function __construct(string $name, string $label = null)
    {
        $this->name = $name;
        $this->label = $label;
    }

    /**
     * @param string[] $issues
     */
    public function callClosures(array &$issues)
    {
        foreach ($this->validationClosures as $validationClosure) {
            $closure = $validationClosure->closure;
            if (! $closure($this->value)) {
                $issues[] = $validationClosure->errorMessage;

                if ($validationClosure->break) {
                    break;
                }
            }
        }
    }

    public abstract function generateField(bool $placeholderLabel): string;

    /**
     * @param string[] $errorMessages
     */
    public abstract function generateGroup(array $errorMessages): string;

    public function hasValue(): bool
    {
        return isset($this->value) && strlen($this->value) > 0;
    }

    /**
     * $this->value has a setter so that it can be overridden by implementing classes (see CheckboxField)
     */
    public function insertValue($value)
    {
        $this->value = $value;
    }

    /**
     * @return string[] contains error messages, empty if valid
     */
    public abstract function validate(): array;
}
