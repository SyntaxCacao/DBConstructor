<?php

declare(strict_types=1);

namespace DBConstructor\Controllers;

abstract class Controller
{
    public function isPublic(): bool
    {
        return false;
    }

    /**
     * @param string[] $path
     */
    public abstract function request(array $path);
}
