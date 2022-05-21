<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\Projects;

use DBConstructor\Application;
use DBConstructor\Forms\Fields\MarkdownField;
use DBConstructor\Forms\Fields\SelectField;
use DBConstructor\Forms\Fields\TextField;
use DBConstructor\Forms\Form;
use DBConstructor\Models\Page;
use DBConstructor\Models\Participant;
use DBConstructor\Models\Project;

class ProjectForm extends Form
{
    /** @var Project|null */
    public $project;

    public function __construct()
    {
        parent::__construct("project-form");
    }

    /**
     * @param Project|null $project null on creation
     */
    public function init(Project $project = null)
    {
        $this->project = $project;

        // label
        $field = new TextField("label", "Bezeichnung");
        $field->minLength = 3;
        $field->maxLength = 64;

        if (! is_null($project)) {
            $field->defaultValue = $project->label;
        }

        $this->addField($field);

        // description
        $field = new TextField("description", "Beschreibung");
        $field->description = "Wird in der Projektübersicht angezeigt";
        $field->expand = true;
        $field->maxLength = 150;
        $field->required = false;

        if (! is_null($project)) {
            $field->defaultValue = $project->description;
        }

        $this->addField($field);

        // notes
        $field = new MarkdownField("notes", "Hinweise");
        $field->description = "Werden neben der Tabellenübersicht angezeigt";
        $field->larger = false;
        $field->maxLength = 20000;
        $field->required = false;

        if (! is_null($project)) {
            $field->defaultValue = $project->notes;
        }

        $this->addField($field);

        // wiki main page
        if (! is_null($project) && ! is_null($project->mainPageId)) {
            $field = new SelectField("mainpage", "Wiki-Hauptseite");
            $field->defaultValue = $project->mainPageId;
            $field->addOptionsObjects(Page::loadList($project->id), "id", "title");
            $this->addField($field);
        }
    }

    public function perform(array $data)
    {
        if (is_null($this->project)) {
            // create
            $id = Project::create($data["label"], $data["description"], $data["notes"]);
            Participant::create(Application::$instance->user->id, $id, true);

            Application::$instance->redirect("projects/$id", "created");
        } else {
            // edit
            $this->project->edit($data["label"], $data["description"], $data["notes"]);

            if (isset($data["mainpage"])) {
                $this->project->setMainPage($data["mainpage"]);
            }
        }
    }
}
