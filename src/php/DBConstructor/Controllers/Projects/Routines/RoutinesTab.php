<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\Projects\Routines;

use DBConstructor\Controllers\NotFoundController;
use DBConstructor\Controllers\TabController;

class RoutinesTab extends TabController
{
    public function __construct()
    {
        parent::__construct("Eingaberoutinen", "routines", "pencil");
    }

    public function request(array $path, array &$data): bool
    {
        if (count($path) != 3) {
            (new NotFoundController())->request($path);
            return false;
        }

        $data["tabpage"] = "list";

        return true;
    }
}
