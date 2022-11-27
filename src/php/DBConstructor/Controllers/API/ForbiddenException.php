<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\API;

use Exception;

class ForbiddenException extends Exception
{
    public function __construct(string $message = "")
    {
        parent::__construct($message);
    }
}
