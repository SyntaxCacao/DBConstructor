<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\Settings\Tokens;

use DBConstructor\Application;
use DBConstructor\Forms\Fields\CheckboxField;
use DBConstructor\Forms\Fields\PasswordField;
use DBConstructor\Forms\Fields\SelectField;
use DBConstructor\Forms\Fields\TextField;
use DBConstructor\Forms\Fields\ValidationClosure;
use DBConstructor\Forms\Form;
use DBConstructor\Models\AccessToken;
use DBConstructor\Models\Project;

abstract class TokenForm extends Form
{
    /** @var string|null */
    public $newToken;

    /** @var string */
    public $verb;

    /** @var string */
    public $verbPerfect;

    public function __construct(string $verb, string $verbPerfect)
    {
        parent::__construct("token-form");

        $this->verb = $verb;
        $this->verbPerfect = $verbPerfect;
    }

    public function addLabelField(string $value = null)
    {
        $field = new TextField("label", "Bezeichnung");
        $field->defaultValue = $value;
        $field->maxLength = 60;
        $field->required = false;

        $this->addField($field);
    }

    public function addExpirationField(bool $includeNull = false)
    {
        $field = new SelectField("expires", "Gültigkeitsdauer");
        $field->addOptions(AccessToken::EXPIRATION_LABELS);

        if ($includeNull) {
            $field->nullLabel = "Unverändert lassen";
            $field->required = false;
        } else {
            $field->defaultValue = AccessToken::EXPIRATION_7_DAYS;
        }

        $this->addField($field);
    }

    /**
     * @param array<Project> $projects
     */
    public function addScopeField(array $projects, AccessToken $token = null)
    {
        if ($token !== null) {
            $currentScope = $token->getScope();
        }

        foreach ($projects as $project) {
            $projectFieldName = "scope-".$project->id;
            $field = new CheckboxField($projectFieldName, "Zugriff gestatten: ".$project->label);

            if ($token !== null) {
                $field->defaultValue = isset($currentScope[$project->id]);
            }

            $this->addField($field);

            $field = new CheckboxField($projectFieldName."-read", "Lesezugriff");
            $field->defaultValue = true;
            $field->dependsOn = $projectFieldName;
            $field->dependsOnValue = CheckboxField::VALUE;
            $field->description = "Schließt das Kommentieren, Kennzeichnen und Zuordnen von Datensätzen ein";
            $field->disabled = true;
            $field->labelBold = false;
            $this->addField($field);

            $field = new CheckboxField($projectFieldName."-write", "Anlegen und Verändern von Datensätzen");
            $field->dependsOn = $projectFieldName;
            $field->dependsOnValue = CheckboxField::VALUE;
            $field->labelBold = false;

            if ($token !== null) {
                $field->defaultValue = isset($currentScope[$project->id]) && in_array(AccessToken::SCOPE_PROJECT_WRITE, $currentScope[$project->id]);
            }

            $this->addField($field);

            $field = new CheckboxField($projectFieldName."-upload", "Dateien an Datensätze anhängen");
            $field->dependsOn = $projectFieldName;
            $field->dependsOnValue = CheckboxField::VALUE;
            $field->labelBold = false;

            if ($token !== null) {
                $field->defaultValue = isset($currentScope[$project->id]) && in_array(AccessToken::SCOPE_PROJECT_UPLOAD, $currentScope[$project->id]);
            }

            $this->addField($field);

            $field = new CheckboxField($projectFieldName."-delete", "Dauerhaftes Löschen von Datensätzen");
            $field->dependsOn = $projectFieldName;
            $field->dependsOnValue = CheckboxField::VALUE;
            $field->labelBold = false;

            if ($token !== null) {
                $field->defaultValue = isset($currentScope[$project->id]) && in_array(AccessToken::SCOPE_PROJECT_DELETE, $currentScope[$project->id]);
            }

            $this->addField($field);

            $field = new CheckboxField($projectFieldName."-structure", "Verändern der Tabellenstrukturen");
            $field->dependsOn = $projectFieldName;
            $field->dependsOnValue = CheckboxField::VALUE;
            $field->labelBold = false;

            if ($token !== null) {
                $field->defaultValue = isset($currentScope[$project->id]) && in_array(AccessToken::SCOPE_PROJECT_STRUCTURE, $currentScope[$project->id]);
            }

            $this->addField($field);
        }
    }

    public function addPasswordField()
    {
        $field = new PasswordField("password", "Mit Passwort bestätigen");
        $field->validationClosures[] = new ValidationClosure(function ($value) {
            return Application::$instance->user->verifyPassword($value);
        }, "Das eingegebene Passwort ist falsch.");
        $this->addField($field);
    }

    public function getVerb(): string
    {
        if ($this->newToken === null) {
            return $this->verb;
        } else {
            return $this->verbPerfect;
        }
    }

    /**
     * @param array<Project> $projects
     * @param array<string, mixed> $data
     */
    public function processScope(array $projects, array $data): array
    {
        $scope = [];

        foreach ($projects as $project) {
            $projectFieldName = "scope-".$project->id;
            $projectScope = [];

            if ($data[$projectFieldName] !== true) {
                continue;
            }

            if ($data[$projectFieldName."-write"] === true) {
                $projectScope[] = AccessToken::SCOPE_PROJECT_WRITE;
            }

            if ($data[$projectFieldName."-upload"] === true) {
                $projectScope[] = AccessToken::SCOPE_PROJECT_UPLOAD;
            }

            if ($data[$projectFieldName."-delete"] === true) {
                $projectScope[] = AccessToken::SCOPE_PROJECT_DELETE;
            }

            if ($data[$projectFieldName."-structure"] === true) {
                $projectScope[] = AccessToken::SCOPE_PROJECT_STRUCTURE;
            }

            $scope[$project->id] = $projectScope;
        }

        return $scope;
    }
}
