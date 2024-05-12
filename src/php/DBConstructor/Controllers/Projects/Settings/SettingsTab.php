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
        parent::__construct("Einstellungen", "settings", "gear", true);
    }

    public function request(array $path, array &$data): bool
    {
        if (count($path) != 3) {
            (new NotFoundController())->request($path);
            return false;
        }

        $form = new ProjectForm();
        $form->init($data["project"]);
        $data["saved"] = $form->process();
        $data["form"] = $form;

        return true;
    }
}
