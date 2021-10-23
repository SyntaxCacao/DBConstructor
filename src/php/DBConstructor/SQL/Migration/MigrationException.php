<?php

declare(strict_types=1);

namespace DBConstructor\SQL\Migration;

use Exception;
use Throwable;

class MigrationException extends Exception
{
    public function __construct(string $message, Throwable $cause = null)
    {
        parent::__construct($message, 0, $cause);
    }
}
