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

    /*
    public $validationClosure;

    public $validationClosureErrorMessage;
    */

    /**
     * Use setter!
     *
     * @var string
     */
    public $value;

    /**
     * @param string|null $label
     */
    public function __construct(string $name, $label = null)
    {
        $this->name = $name;
        $this->label = $label;
    }

    public function callClosures(&$issues)
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

    public abstract function validate(): array;
}
