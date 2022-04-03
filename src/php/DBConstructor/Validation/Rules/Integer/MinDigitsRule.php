<?php

declare(strict_types=1);

namespace DBConstructor\Validation\Rules\Integer;

use DBConstructor\Validation\Rules\Rule;

class MinDigitsRule extends Rule
{
    /** @var int */
    public $minDigits;

    public function __construct(int $minDigits, int $depends)
    {
        $this->depends[] = $depends;
        $this->minDigits = $minDigits;

        if ($minDigits == 1) {
            $this->description = "Wenigstens eine Stelle";
        } else {
            $this->description = "Wenigstens $minDigits Stellen";
        }
    }

    public function validate(string $value = null)
    {
        if ($value !== null) {
            $matches = [];
            $result = preg_match("/^(?:0|-?[1-9]+[0-9]*)$/D", $value, $matches);
            $this->setResult($result === 1 && strlen(trim($value, "-")) >= $this->minDigits);
        }
    }
}
