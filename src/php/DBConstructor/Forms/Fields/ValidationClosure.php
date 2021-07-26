<?php

declare(strict_types=1);

namespace DBConstructor\Forms\Fields;

class ValidationClosure
{
    /** @var bool */
    public $break;

    /** @var callable */
    public $closure;

    /** @var string */
    public $errorMessage;

    public function __construct(callable $closure, string $errorMessage, bool $break = false)
    {
        $this->closure = $closure;
        $this->errorMessage = $errorMessage;
        $this->break = $break;
    }
}
