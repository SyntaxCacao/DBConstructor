<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\Projects\Workflows;

use DBConstructor\Application;
use DBConstructor\Forms\Fields\TextField;
use DBConstructor\Forms\Form;
use DBConstructor\Models\Workflow;

class WorkflowForm extends Form
{
    /** @var string|null */
    public $projectId;

    /** @var Workflow|null */
    public $workflow;

    public function __construct()
    {
        parent::__construct("workflow-form");
    }

    public function init(string $projectId, Workflow $workflow = null)
    {
        $this->projectId = $projectId;
        $this->workflow = $workflow;

        // label
        $field = new TextField("label", "Bezeichnung");
        $field->minLength = 3;
        $field->maxLength = 60;

        if ($workflow !== null) {
            $field->defaultValue = $workflow->label;
        }

        $this->addField($field);

        // description
        $field = new TextField("description", "Beschreibung");
        $field->expand = true;
        $field->maxLength = 150;
        $field->required = false;

        if ($workflow !== null) {
            $field->defaultValue = $workflow->description;
        }

        $this->addField($field);
    }

    public function perform(array $data)
    {
        if ($this->workflow === null) {
            // create
            $id = Workflow::create($this->projectId, Application::$instance->user->id, $data["label"], $data["description"]);
            Application::$instance->redirect("projects/$this->projectId/workflows/$id/steps");
        } else {
            // edit
            $this->workflow->edit(Application::$instance->user->id, $data["label"], $data["description"]);
            Application::$instance->redirect("projects/$this->projectId/workflows/".$this->workflow->id."/steps", "saved");
        }
    }
}
