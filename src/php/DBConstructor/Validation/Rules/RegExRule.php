<?php

declare(strict_types=1);

namespace DBConstructor\Validation\Rules;

class RegExRule extends Rule
{
    /** @var string */
    public $regEx;

    public function __construct(string $regEx)
    {
        $this->description = "Entspricht dieser RegEx: <br><code>".htmlentities($regEx)."</code>";
        $this->regEx = $regEx;
    }

    public function validate(string $value = null)
    {
        if ($value !== null) {
            $this->setResult(preg_match($this->regEx, $value) === 1);
        }
    }
}
