<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\Settings;

use DBConstructor\Application;
use DBConstructor\Forms\Fields\PasswordField;
use DBConstructor\Forms\Fields\ValidationClosure;
use DBConstructor\Forms\Form;

class PasswordChangeForm extends Form
{
    public function __construct()
    {
        parent::__construct("user-settings-password");
    }

    public function init()
    {
        $field = new PasswordField("old-password", "Bisheriges Passwort");
        $field->validationClosures[] = new ValidationClosure(function($value) {
            return Application::$instance->user->verifyPassword($value);
        },"Das eingegebene Passwort ist falsch.");
        $this->addField($field);

        $field = new PasswordField("new-password", "Neues Passwort");
        $field->minLength = 5;
        $this->addField($field);
    }

    public function perform(array $data)
    {
        Application::$instance->user->setPassword($data["new-password"]);
    }
}
