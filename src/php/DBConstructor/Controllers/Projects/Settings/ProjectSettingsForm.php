<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\Projects\Settings;

use DBConstructor\Forms\Fields\SelectField;
use DBConstructor\Forms\Form;
use DBConstructor\Forms\Fields\MarkdownField;
use DBConstructor\Forms\Fields\TextField;
use DBConstructor\Models\Page;
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

        $field = new MarkdownField("description", "Beschreibung", false);
        $field->defaultValue = $project->description;
        $field->maxLength = 1000;
        $field->required = false;
        $this->addField($field);

        if (! is_null($project->mainPageId)) {
            $field = new SelectField("mainpage", "Wiki-Hauptseite");
            $field->defaultValue = $project->mainPageId;
            $field->addOptionsObjects(Page::loadList($project->id), "id", "title");
            $this->addField($field);
        }
    }

    public function perform(array $data)
    {
        $this->project->edit($data["label"], $data["description"]);

        if (isset($data["mainpage"])) {
            $this->project->setMainPage($data["mainpage"]);
        }
    }
}
