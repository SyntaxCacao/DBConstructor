<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\Projects;

use DBConstructor\Application;
use DBConstructor\Forms\Form;
use DBConstructor\Forms\Fields\TextField;
use DBConstructor\Forms\Fields\TextareaField;
use DBConstructor\Models\Participant;
use DBConstructor\Models\Project;

class ProjectCreateForm extends Form
{
    public function __construct()
    {
        parent::__construct("project-create-form");
    }

    public function init()
    {
        $field = new TextField("label", "Bezeichnung");
        $field->minLength = 3;
        $field->maxLength = 30;
        $this->addField($field);

        $field = new TextareaField("description", "Beschreibung");
        $field->maxLength = 1000;
        $field->required = false;
        $this->addField($field);
    }

    public function perform(array $data)
    {
        $id = Project::create($data["label"], $data["description"]);
        Participant::create(Application::$instance->user->id, $id, true);

        Application::$instance->redirect("projects/$id", "created");
    }
}
