<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\Projects\Workflows\Steps;

use DBConstructor\Application;
use DBConstructor\Controllers\Projects\ProjectsController;
use DBConstructor\Forms\Fields\MarkdownField;
use DBConstructor\Forms\Fields\SelectField;
use DBConstructor\Forms\Fields\TextField;
use DBConstructor\Forms\Form;
use DBConstructor\Models\RelationalColumn;
use DBConstructor\Models\TextualColumn;
use DBConstructor\Models\Workflow;
use DBConstructor\Models\WorkflowStep;
use DBConstructor\Util\JsonException;

class StepEditForm extends Form
{
    /** @var array<RelationalColumn> */
    public $relationalColumns;

    /** @var WorkflowStep */
    public $step;

    /** @var array<TextualColumn> */
    public $textualColumns;

    /** @var Workflow */
    public $workflow;

    public function __construct()
    {
        parent::__construct("step-edit-form");
    }

    /**
     * @param array<WorkflowStep> $steps
     * @param array<RelationalColumn> $relationalColumns
     * @param array<TextualColumn> $textualColumns
     * @throws JsonException
     */
    public function init(Workflow $workflow, array $steps, WorkflowStep $step, array $relationalColumns, array $textualColumns)
    {
        $this->workflow = $workflow;
        $this->step = $step;
        $this->relationalColumns = $relationalColumns;
        $this->textualColumns = $textualColumns;

        // label
        $field = new TextField("label", "Bezeichnung");
        $field->defaultValue = $step->label;
        $field->maxLength = 64;
        $field->required = false;

        $this->addField($field);

        // instructions
        $field = new MarkdownField("instructions", "Erläuterungen");
        $field->defaultValue = $step->instructions;
        $field->maxLength = 20000;
        $field->larger = false;
        $field->required = false;

        $this->addField($field);

        $relationalColumnData = WorkflowStep::readRelationalColumnData($relationalColumns, $step->relationalColumnData);
        $textualColumnData = WorkflowStep::readTextualColumnData($textualColumns, $step->textualColumnData);

        foreach ($relationalColumns as $column) {
            $possibleFillInSteps = [];

            foreach ($steps as $workflowStep) {
                if ($workflowStep->position < $step->position && $workflowStep->tableId === $column->targetTableId) {
                    $possibleFillInSteps[] = $workflowStep;
                }
            }

            // type
            $field = new SelectField("rel-".$column->id."-type", $column->label);
            $field->addOption(WorkflowStep::DATA_TYPE_INPUT, WorkflowStep::DATA_TYPES[WorkflowStep::DATA_TYPE_INPUT]);

            if (count($possibleFillInSteps) > 0) {
                $field->addOption(WorkflowStep::DATA_TYPE_FILL_ID, WorkflowStep::DATA_TYPES[WorkflowStep::DATA_TYPE_FILL_ID]);
            }

            $field->addOption(WorkflowStep::DATA_TYPE_EXCLUDE, WorkflowStep::DATA_TYPES[WorkflowStep::DATA_TYPE_EXCLUDE]);
            $field->defaultValue = $relationalColumnData[$column->id][WorkflowStep::DATA_KEY_TYPE];

            $this->addField($field);

            // fill-in
            $field = new SelectField("rel-".$column->id."-fill-in", "Eingabeschritt wählen");
            $field->dependsOn = "rel-".$column->id."-type";
            $field->dependsOnValue = WorkflowStep::DATA_TYPE_FILL_ID;

            if (isset($relationalColumnData[$column->id][WorkflowStep::DATA_KEY_FILL_IN])) {
                $field->defaultValue = $relationalColumnData[$column->id][WorkflowStep::DATA_KEY_FILL_IN];
            }

            foreach ($possibleFillInSteps as $workflowStep) {
                $field->addOption($workflowStep->id, $workflowStep->getLabel());
            }

            $this->addField($field);
        }

        $first = true;

        foreach ($textualColumns as $column) {
            // type
            $field = new SelectField("txt-".$column->id."-type", $column->label);
            $field->addOption(WorkflowStep::DATA_TYPE_INPUT, WorkflowStep::DATA_TYPES[WorkflowStep::DATA_TYPE_INPUT]);

            if ($first) {
                $first = false;
            } else {
                $field->addOption(WorkflowStep::DATA_TYPE_DEPENDING, WorkflowStep::DATA_TYPES[WorkflowStep::DATA_TYPE_DEPENDING]);
            }

            $field->addOption(WorkflowStep::DATA_TYPE_STATIC, WorkflowStep::DATA_TYPES[WorkflowStep::DATA_TYPE_STATIC]);
            $field->addOption(WorkflowStep::DATA_TYPE_EXCLUDE, WorkflowStep::DATA_TYPES[WorkflowStep::DATA_TYPE_EXCLUDE]);
            $field->defaultValue = $textualColumnData[$column->id][WorkflowStep::DATA_KEY_TYPE];

            $this->addField($field);

            // depending-field
            $field = new SelectField("txt-".$column->id."-depending-field", "Feld wählen");
            $field->dependsOn = "txt-".$column->id."-type";
            $field->dependsOnValue = WorkflowStep::DATA_TYPE_DEPENDING;
            $field->description = "Wählen Sie das Feld, von dem die Anzeige dieses Feldes abhängen soll";

            if (isset($textualColumnData[$column->id][WorkflowStep::DATA_KEY_DEPENDING_FIELD])) {
                $field->defaultValue = $textualColumnData[$column->id][WorkflowStep::DATA_KEY_DEPENDING_FIELD];
            }

            foreach ($textualColumns as $textualColumn) {
                //if ($textualColumn->id !== $column->id) { // Might lead to recursion
                if ($textualColumn->position < $column->position) {
                    $field->addOption($textualColumn->id, $textualColumn->label);
                }
            }

            $this->addField($field);

            // depending-value
            $field = new TextField("txt-".$column->id."-depending-value", "Wert eingeben");
            $field->dependsOn = "txt-".$column->id."-type";
            $field->dependsOnValue = WorkflowStep::DATA_TYPE_DEPENDING;
            $field->description = "Geben Sie den Wert ein, den das ausgewählte Feld haben soll, damit dieses Feld gezeigt wird. Wenn das Feld eine Auswahl ist, muss der technische Name einer Option exakt eingeben werden";
            $field->maxLength = 100;
            $field->required = false;
            // Closure will not be called if no value is inserted!
            $field->validationClosures[] = new DependingValueClosure($textualColumns, $column->id);

            if (isset($textualColumnData[$column->id][WorkflowStep::DATA_KEY_DEPENDING_VALUE])) {
                $field->defaultValue = $textualColumnData[$column->id][WorkflowStep::DATA_KEY_DEPENDING_VALUE];
            }

            $this->addField($field);

            // static-value
            $field = new TextField("txt-".$column->id."-static-value", "Wert eingeben");
            $field->dependsOn = "txt-".$column->id."-type";
            $field->dependsOnValue = WorkflowStep::DATA_TYPE_STATIC;
            $field->maxLength = 100;
            // Closure will not be called if no value is inserted!
            $field->validationClosures[] = new StaticValueClosure($column);

            if (isset($textualColumnData[$column->id][WorkflowStep::DATA_KEY_STATIC_VALUE])) {
                $field->defaultValue = $textualColumnData[$column->id][WorkflowStep::DATA_KEY_STATIC_VALUE];
            }

            $this->addField($field);
        }
    }

    /**
     * @throws JsonException
     */
    public function perform(array $data)
    {
        $relationalColumnData = WorkflowStep::writeRelationalColumnData($this->relationalColumns, $data);
        $textualColumnData = WorkflowStep::writeTextualColumnData($this->textualColumns, $data);
        $this->step->edit($this->workflow, Application::$instance->user->id, $data["label"], $data["instructions"], $relationalColumnData, $textualColumnData);
        Application::$instance->redirect("projects/".ProjectsController::$projectId."/workflows/".$this->workflow->id."/steps", "saved");
    }
}
