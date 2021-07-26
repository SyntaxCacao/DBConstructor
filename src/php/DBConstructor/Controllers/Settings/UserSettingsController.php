<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\Settings;

use DBConstructor\Application;
use DBConstructor\Controllers\Controller;
use DBConstructor\Controllers\NotFoundController;

class UserSettingsController extends Controller
{
    public function request(array $path)
    {
        if (count($path) != 1) {
            (new NotFoundController())->request($path);
            return;
        }

        $nameForm = new NameChangeForm();
        $nameForm->init();
        $nameSuccess = $nameForm->process();

        $passwordForm = new PasswordChangeForm();
        $passwordForm->init();
        $passwordSuccess = $passwordForm->process();

        $data["page"] = "user_settings";
        $data["title"] = "Einstellungen";
        $data["name-form"] = $nameForm;
        $data["name-success"] = $nameSuccess;
        $data["password-form"] = $passwordForm;
        $data["password-success"] = $passwordSuccess;

        Application::$instance->callTemplate($data);
    }
}
