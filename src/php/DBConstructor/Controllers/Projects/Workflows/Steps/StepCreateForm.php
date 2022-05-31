<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\Projects\Workflows\Steps;

use DBConstructor\Application;
use DBConstructor\Controllers\Projects\ProjectsController;
use DBConstructor\Forms\Fields\SelectField;
use DBConstructor\Forms\Form;
use DBConstructor\Models\Table;
use DBConstructor\Models\Workflow;
use DBConstructor\Models\WorkflowStep;

class StepCreateForm extends Form
{
    /** @var Workflow */
    public $workflow;

    public function __construct()
    {
        parent::__construct("step-create-form");
    }

    public function init(Workflow $workflow)
    {
        $this->workflow = $workflow;

        $this->buttonLabel = "Weiter";

        $field = new SelectField("table", "Tabelle wählen");
        $field->description = "Die Auswahl kann später nicht mehr verändert werden.";

        $tables = Table::loadList(ProjectsController::$projectId);

        foreach ($tables as $table) {
            $field->addOption($table->id, $table->label);
        }

        $this->addField($field);
    }

    public function perform(array $data)
    {
        $stepId = WorkflowStep::create($this->workflow, Application::$instance->user->id, $data["table"]);
        Application::$instance->redirect("projects/".ProjectsController::$projectId."/workflows/".$this->workflow->id."/steps/$stepId/edit");
    }
}
