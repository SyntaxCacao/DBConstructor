<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\Projects\Tables\Issues;

use DBConstructor\Controllers\NotFoundController;
use DBConstructor\Controllers\TabController;

class IssuesTab extends TabController
{
    public function __construct()
    {
        parent::__construct("Probleme", "issues", "chat-left-text");
    }

    public function request(array $path, &$data): bool
    {
        if (count($path) != 5) {
            (new NotFoundController())->request($path);
            return false;
        }

        return true;
    }
}
