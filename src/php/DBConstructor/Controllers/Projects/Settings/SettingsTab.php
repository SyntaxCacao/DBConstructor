<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\Projects\Settings;

use DBConstructor\Controllers\NotFoundController;
use DBConstructor\Controllers\Projects\ProjectForm;
use DBConstructor\Controllers\TabController;

class SettingsTab extends TabController
{
    public function __construct()
    {
        parent::__construct("Einstellungen", "settings", "gear");
    }

    public function request(array $path, &$data): bool
    {
        if (count($path) != 3) {
            (new NotFoundController())->request($path);
            return false;
        }

        if (! $data["isManager"]) {
            $data["forbidden"] = true;
            return true;
        }

        $form = new ProjectForm();
        $form->init($data["project"]);
        $data["saved"] = $form->process();
        $data["form"] = $form;

        return true;
    }
}
