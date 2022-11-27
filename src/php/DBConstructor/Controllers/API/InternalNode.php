<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\API;

use Exception;

abstract class InternalNode
{
    /**
     * @throws Exception
     */
    public abstract function process($path): array;
}
