<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\Projects\Workflows\Steps;

use DBConstructor\Application;
use DBConstructor\Forms\Fields\SelectField;
use DBConstructor\Forms\Form;
use DBConstructor\Models\Table;
use DBConstructor\Models\Workflow;
use DBConstructor\Models\WorkflowStep;

class StepCreateForm extends Form
{
    /** @var string */
    public $projectId;

    /** @var Workflow */
    public $workflow;

    public function __construct()
    {
        parent::__construct("step-create-form");
    }

    public function init(string $projectId, Workflow $workflow)
    {
        $this->projectId = $projectId;
        $this->workflow = $workflow;

        $this->buttonLabel = "Weiter";

        $field = new SelectField("table", "Tabelle wählen");
        $field->description = "Die Auswahl kann später nicht mehr verändert werden.";

        $tables = Table::loadList($projectId);

        foreach ($tables as $table) {
            $field->addOption($table["obj"]->id, $table["obj"]->label);
        }

        $this->addField($field);
    }

    public function perform(array $data)
    {
        $stepId = WorkflowStep::create($this->workflow, Application::$instance->user->id, $data["table"]);
        Application::$instance->redirect("projects/$this->projectId/workflows/".$this->workflow->id."/steps/$stepId/edit");
    }
}
