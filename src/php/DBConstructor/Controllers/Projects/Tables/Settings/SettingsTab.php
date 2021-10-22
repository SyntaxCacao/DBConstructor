<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\Projects\Tables\Settings;

use DBConstructor\Controllers\NotFoundController;
use DBConstructor\Controllers\Projects\Tables\TableForm;
use DBConstructor\Controllers\TabController;

class SettingsTab extends TabController
{
    public function __construct()
    {
        parent::__construct("Einstellungen", "settings", "gear");
    }

    public function request(array $path, array &$data): bool
    {
        if (count($path) != 5) {
            (new NotFoundController())->request($path);
            return false;
        }

        if (! $data["isManager"]) {
            $data["forbidden"] = true;
            return true;
        }

        $form = new TableForm();
        $form->init($data["project"]->id, $data["table"]);
        $data["saved"] = $form->process();
        $data["form"] = $form;

        return true;
    }
}
