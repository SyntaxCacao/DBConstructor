<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\Users;

use DBConstructor\Application;
use DBConstructor\Forms\Fields\CheckboxField;
use DBConstructor\Forms\Fields\PasswordField;
use DBConstructor\Forms\Fields\TextField;
use DBConstructor\Forms\Fields\ValidationClosure;
use DBConstructor\Forms\Form;
use DBConstructor\Models\User;

class UserForm extends Form
{
    /** @var User|null */
    public $user;

    public function __construct()
    {
        parent::__construct("user");
    }

    /**
     * @param User|null $user null = create, User = edit
     */
    public function init(User $user = null)
    {
        $this->user = $user;

        // id
        if (! is_null($user)) {
            $field = new TextField("id", "ID");
            $field->value = $user->id;
            $field->disabled = true;
            $this->addField($field);
        }

        // username
        $field = new TextField("username", "Benutzername");
        $field->description = "Der Benutzername wird bei der Anmeldung verwendet.";
        $field->maxLength = 20;
        $field->minLength = 3;
        $field->validationClosures[] = new UsernamePatternClosure();

        if (is_null($user)) {
            $field->validationClosures[] = new ValidationClosure(static function ($value) {
                return User::isUsernameAvailable($value);
            }, "Dieser Benutzername ist bereits vergeben.");
        } else {
            $field->validationClosures[] = new ValidationClosure(function ($value) {
                return $value == $this->user->username || User::isUsernameAvailable($value);
            }, "Dieser Benutzername ist bereits vergeben.");

            $field->defaultValue = $user->username;
        }

        $this->addField($field);

        // firstname
        $field = new TextField("firstname", "Vorname");
        $field->maxLength = 30;
        $field->minLength = 3;

        if (! is_null($user)) {
            $field->defaultValue = $user->firstname;
        }

        $this->addField($field);

        // lastname
        $field = new TextField("lastname", "Nachname");
        $field->maxLength = 30;
        $field->minLength = 3;

        if (! is_null($user)) {
            $field->defaultValue = $user->lastname;
        }

        $this->addField($field);

        // password
        $field = new PasswordField("password", "Passwort");
        $field->minLength = 5;

        if (! is_null($user)) {
            $field->label = "Neues Passwort";
            $field->required = false;
        }

        $this->addField($field);

        // admin
        $field = new CheckboxField("admin", "Administrator");
        $field->description = "Kann Projekte anlegen und Benutzer verwalten";

        if (! is_null($user)) {
            $field->defaultValue = $user->isAdmin;
        }

        $this->addField($field);

        // locked
        if (! is_null($user)) {
            $field = new CheckboxField("locked", "Gesperrt");
            $field->description = "Kann sich nicht mehr anmelden";
            $field->defaultValue = $user->locked;
            $this->addField($field);
        }
    }

    public function perform(array $data)
    {
        if (is_null($this->user)) {
            // create
            User::create($data["username"], $data["firstname"], $data["lastname"], $data["password"], $data["admin"]);
            Application::$instance->redirect("users", "created");
        } else {
            // edit
            $this->user->edit($data["username"], $data["firstname"], $data["lastname"], $data["admin"], $data["locked"]);

            if (! is_null($data["password"])) {
                $this->user->setPassword($data["password"]);
            }

            Application::$instance->redirect("users", "edited");
        }
    }
}
