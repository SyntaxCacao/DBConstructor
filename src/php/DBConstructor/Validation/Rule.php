<?php

declare(strict_types=1);

namespace DBConstructor\Validation;

abstract class Rule
{
    const RULES = [
        "maxLength" => MaxLengthRule::class,
        "maxValue" => MaxValueRule::class,
        "minLength" => MinLengthRule::class,
        "minValue" => MinValueRule::class,
        "notNull" => NotNullRule::class,
        "regex" => RegexRule::class,
        "targetRowExists" => TargetRowExistsRule::class,
        "unsigned" => UnsignedRule::class
    ];

    public $acceptInvalidType = false;

    /** @var bool */
    public $acceptNull = false;

    /** @var string */
    public $label;

    /** @var string */
    public $ruleValue;

    public function __construct(string $label, string $ruleValue)
    {
        $this->label = $label;
        $this->ruleValue = $ruleValue;
    }

    public function getName(): string
    {
        return array_search(get_class($this), Rule::RULES);
    }

    public abstract function validate(string $value): bool;
}
