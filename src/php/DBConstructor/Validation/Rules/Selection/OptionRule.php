<?php

declare(strict_types=1);

namespace DBConstructor\Validation\Rules\Selection;

use DBConstructor\Validation\Rules\Rule;
use DBConstructor\Validation\Types\SelectionType;

class OptionRule extends Rule
{
    /** @var bool */
    public $allowMultiple;

    /** @var array<string> */
    public $options;

    /**
     * @param array<string> $options
     */
    public function __construct(array $options, bool $allowMultiple)
    {
        $this->allowMultiple = $allowMultiple;
        $this->options = $options;

        if ($allowMultiple) {
            $this->description = "Zulässige Optionen gewählt";
        } else {
            $this->description = "Zulässige Option gewählt";
        }
    }

    public function validate(string $value = null)
    {
        if ($value !== null) {
            if ($this->allowMultiple) {
                $selected = explode(SelectionType::INTERNAL_SEPARATOR, $value);
                $this->result = Rule::RESULT_VALID;

                foreach ($selected as $item) {
                    if (! in_array($item, $this->options)) {
                        $this->result = Rule::RESULT_INVALID;
                    }
                }
            } else {
                $this->setResult(in_array($value, $this->options));
            }
        }
    }
}
