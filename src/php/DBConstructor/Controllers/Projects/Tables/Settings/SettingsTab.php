<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\Projects\Tables\Settings;

use DBConstructor\Controllers\NotFoundController;
use DBConstructor\Controllers\TabController;

class SettingsTab extends TabController
{
    public function __construct()
    {
        parent::__construct("Einstellungen", "settings", "gear");
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
