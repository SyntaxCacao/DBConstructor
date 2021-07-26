<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\Projects\Tables;

use DBConstructor\Application;
use DBConstructor\Forms\Fields\ValidationClosure;
use DBConstructor\Forms\Form;
use DBConstructor\Forms\Fields\TextField;
use DBConstructor\Forms\Fields\TextareaField;
use DBConstructor\Models\Table;

class TableCreateForm extends Form
{
    public $projectId;

    public function __construct()
    {
        parent::__construct("table-create-form");
    }

    public function init(string $projectId)
    {
        $this->projectId = $projectId;

        $field = new TextField("label", "Bezeichnung");
        $field->minLength = 3;
        $field->maxLength = 30;
        $this->addField($field);

        $field = new TextField("name", "Technischer Name");
        $field->maxLength = 30;
        $field->monospace = true;
        $field->validationClosures[] = new ValidationClosure(function ($value) {
            return preg_match("/^[A-Za-z0-9-_]+$/", $value);
        }, "Spaltennamen dürfen nur alphanumerische Zeichen, Bindestriche und Unterstriche enthalten.", true);
        $this->addField($field);

        $field = new TextareaField("description", "Beschreibung");
        $field->maxLength = 1000;
        $field->required = false;
        $this->addField($field);
    }

    public function perform(array $data)
    {
        $id = Table::create($this->projectId, $data["name"], $data["label"], $data["description"]);

        Application::$instance->redirect("projects/$this->projectId/tables/$id", "created");
    }
}
