<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\Projects\Settings;

use DBConstructor\Forms\Form;
use DBConstructor\Forms\Fields\TextField;
use DBConstructor\Forms\Fields\TextareaField;
use DBConstructor\Models\Project;

class ProjectSettingsForm extends Form
{
    /** @var Project */
    public $project;

    public function __construct()
    {
        parent::__construct("project-settings-form");
    }

    public function init(Project $project)
    {
        $this->project = $project;

        $field = new TextField("label", "Bezeichnung");
        $field->defaultValue = $project->label;
        $field->minLength = 3;
        $field->maxLength = 30;
        $this->addField($field);

        $field = new TextareaField("description", "Beschreibung");
        $field->defaultValue = $project->description;
        $field->maxLength = 1000;
        $field->required = false;
        $this->addField($field);
    }

    public function perform(array $data)
    {
        $this->project->edit($data["label"], $data["description"]);
    }
}
