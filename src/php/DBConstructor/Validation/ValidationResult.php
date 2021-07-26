<?php

declare(strict_types=1);

namespace DBConstructor\Validation;

class ValidationResult
{
    /**
     * @var Rule[]
     */
    public $failed = [];

    /**
     * @var bool
     */
    public $valid = true;
}
