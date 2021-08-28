<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\Settings;

use DBConstructor\Application;
use DBConstructor\Controllers\Users\UsernamePatternClosure;
use DBConstructor\Forms\Fields\TextField;
use DBConstructor\Forms\Fields\ValidationClosure;
use DBConstructor\Forms\Form;
use DBConstructor\Models\User;

class NameChangeForm extends Form
{
    public function __construct()
    {
        parent::__construct("user-settings-name");
    }

    public function init()
    {
        $field = new TextField("username", "Benutzername");
        $field->defaultValue = Application::$instance->user->username;
        $field->maxLength = 20;
        $field->minLength = 3;

        if (Application::$instance->hasAdminPermissions()) {
            $field->description = "Der Benutzername wird bei der Anmeldung verwendet.";
        } else {
            $field->disabled = true;
            $field->required = false;
            $field->description = "Nur Administratoren können Benutzernamen ändern";
        }

        $field->validationClosures[] = new UsernamePatternClosure();
        $field->validationClosures[] = new ValidationClosure(static function ($value) {
            return $value == Application::$instance->user->username || User::isUsernameAvailable($value);
        }, "Dieser Benutzername ist bereits vergeben.");

        $this->addField($field);

        $field = new TextField("firstname", "Vorname");
        $field->defaultValue = Application::$instance->user->firstname;
        $field->maxLength = 30;
        $this->addField($field);

        $field = new TextField("lastname", "Nachname");
        $field->defaultValue = Application::$instance->user->lastname;
        $field->maxLength = 30;
        $this->addField($field);
    }

    public function perform(array $data)
    {
        if (isset($data["username"])) {
            Application::$instance->user->setUsername($data["username"]);
        }

        Application::$instance->user->setName($data["firstname"], $data["lastname"]);
    }
}
