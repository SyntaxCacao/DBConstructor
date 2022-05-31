<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\Projects\Workflows\Executions;

use DBConstructor\Application;
use DBConstructor\Controllers\Projects\ProjectsController;
use DBConstructor\Controllers\Projects\Tables\RowForm;
use DBConstructor\Forms\Fields\CheckboxField;
use DBConstructor\Forms\Fields\MarkdownField;
use DBConstructor\Forms\Fields\SelectField;
use DBConstructor\Models\Participant;
use DBConstructor\Models\RelationalColumn;
use DBConstructor\Models\RelationalField;
use DBConstructor\Models\Row;
use DBConstructor\Models\TextualColumn;
use DBConstructor\Models\TextualField;
use DBConstructor\Models\WorkflowExecution;
use DBConstructor\Models\WorkflowStep;
use DBConstructor\Util\JsonException;
use DBConstructor\Util\MarkdownParser;

class ExecutionForm extends RowForm
{
    /** @var array<string, array{field: string, value: string}> */
    public $depending = [];

    /** @var array<string, array<string, RelationalColumn>> */
    public $relationalColumnTable;

    /** @var array<string, array<string, array<string, string>>> */
    public $relationalDataTable = [];

    /** @var array<WorkflowStep> */
    public $steps;

    /** @var array<string, array<string, string>> */
    public $stepsFields = [];

    /** @var array<string, array<string, TextualColumn>> */
    public $textualColumnTable;

    /** @var array<string, array<string, array<string, string>>> */
    public $textualDataTable = [];

    /** @var string */
    public $workflowId;

    public function __construct()
    {
        parent::__construct("execution-form", false);
    }

    /**
     * @param array<WorkflowStep> $steps
     * @param array<string, array<string, RelationalColumn>> $relationalColumns
     * @param array<string, array<string, TextualColumn>> $textualColumns
     * @throws JsonException
     */
    public function init(string $workflowId, array $steps, array $relationalColumns, array $textualColumns)
    {
        $this->workflowId = $workflowId;
        $this->steps = $steps;
        $this->relationalColumnTable = $relationalColumns;
        $this->textualColumnTable = $textualColumns;

        foreach ($steps as $step) {
            $this->relationalDataTable[$step->id] = WorkflowStep::readRelationalColumnData($relationalColumns[$step->tableId], $step->relationalColumnData);
            $this->textualDataTable[$step->id] = WorkflowStep::readTextualColumnData($textualColumns[$step->tableId], $step->textualColumnData);
        }

        $participants = Participant::loadList(ProjectsController::$projectId);

        foreach ($steps as $step) {
            foreach ($this->relationalDataTable[$step->id] as $columnId => $columnData) {
                if ($columnData[WorkflowStep::DATA_KEY_TYPE] === WorkflowStep::DATA_TYPE_INPUT) {
                    $fieldName = $this->addRelationalField($relationalColumns[$step->tableId][$columnId], null, $step->id);
                    $this->stepsFields[$step->id][] = $fieldName;
                }
            }

            foreach ($this->textualDataTable[$step->id] as $columnId => $columnData) {
                if ($columnData[WorkflowStep::DATA_KEY_TYPE] === WorkflowStep::DATA_TYPE_INPUT) {
                    $fieldName = $this->addTextualField($textualColumns[$step->tableId][$columnId], null, $step->id);
                    $this->stepsFields[$step->id][] = $fieldName;
                } else if ($columnData[WorkflowStep::DATA_KEY_TYPE] === WorkflowStep::DATA_TYPE_DEPENDING) {
                    $fieldName = $this->addTextualField($textualColumns[$step->tableId][$columnId], null, $step->id);
                    $this->stepsFields[$step->id][] = $fieldName;

                    $dependingId = $columnData[WorkflowStep::DATA_KEY_DEPENDING_FIELD];
                    $dependingValue = $columnData[WorkflowStep::DATA_KEY_DEPENDING_VALUE];
                    $this->depending[$fieldName] = [
                        "field" => "step-".$step->id."-textual-".$dependingId,
                        "value" => $dependingValue
                    ];
                }
            }

            // TODO: Reduce redunandcy (InsertForm)
            // comment
            $field = new MarkdownField("step-".$step->id."-comment", "Kommentar");
            $field->description = "Halten Sie hier etwa Unklarheiten bei der Datenerfassung fest";
            $field->larger = false;
            $field->maxLength = 1000;
            $field->required = false;

            $this->addField($field);

            // flag
            $field = new CheckboxField("step-".$step->id."-flag", "Zur Nachverfolgung kennzeichnen");
            $field->description = "Kennzeichen Sie diesen Datensatz, wenn noch Klärungsbedarf besteht";

            $this->addField($field);

            // assignee
            $field = new SelectField("step-".$step->id."-assignee", "Jemandem zuordnen", "Keine Auswahl");
            $field->description = "Ordnen Sie den Datensatz einem Projektbeteiligten zur weiteren Bearbeitung zu";
            $field->required = false;

            $field->addOption(Application::$instance->user->id, "Mir zuordnen");

            foreach ($participants as $participant) {
                if ($participant->userId != Application::$instance->user->id) {
                    $field->addOption($participant->userId, $participant->lastName.", ".$participant->firstName);
                }
            }

            $this->addField($field);
        }
    }

    public function generateFields()
    {
        $multiple = count($this->steps) > 1;

        foreach ($this->steps as $step) {
            if ($multiple) {
                echo '<h2 class="main-subheading"><em>'.htmlentities($step->getLabel()).'</em></h2>';
            }

            if ($step->instructions !== null || $step->tableInstructions !== null) {
                echo '<div class="markdown">';

                if ($step->instructions !== null) {
                    echo MarkdownParser::parse($step->instructions);
                }

                if ($step->tableInstructions !== null) {
                    echo MarkdownParser::parse($step->tableInstructions);
                }

                echo '</div>';
            }

            foreach ($this->stepsFields[$step->id] as $fieldName) {
                if (array_key_exists($fieldName, $this->issues)) {
                    $errorMessages = $this->issues[$fieldName];
                } else {
                    $errorMessages = [];
                }

                if (isset($this->depending[$fieldName])) {
                    // TODO: Find solution that doesn't require style attribute
                    echo '<div class="form-group-depend" data-depends-on="'.htmlentities($this->depending[$fieldName]["field"]).'" data-depends-on-value="'.htmlentities($this->depending[$fieldName]["value"]).'" style="padding-left: 0">';
                }

                $this->columns[$fieldName]->generateInput($this->fields[$fieldName], $errorMessages, $this->isEdit);

                if (isset($this->depending[$fieldName])) {
                    echo '</div>';
                }
            }

            if (! $multiple) {
                echo '<hr style="margin: 32px 0">';
            }

            if ($multiple) {
                // TODO: Eradicate style attributes
                echo '<details style="margin-top: 32px">';
                echo '<summary><span>Kommentar, Kennzeichnung, Zuordnung usw.</span></summary>';
                echo '<div style="margin-top: 32px">';
            }

            echo $this->fields["step-$step->id-comment"]->generateGroup();
            echo $this->fields["step-$step->id-flag"]->generateGroup();
            echo $this->fields["step-$step->id-assignee"]->generateGroup();

            if ($multiple) {
                echo '</div></details>';
                echo '<hr style="margin: 32px 0">';
            }
        }
    }

    /**
     * @throws JsonException
     */
    public function perform(array $data)
    {
        $ids = [];

        foreach ($this->steps as $step) {
            // Assemble fields and perform validation for textual fields

            $relationalColumns = $this->relationalColumnTable[$step->tableId];
            $relationalFields = [];

            foreach ($relationalColumns as $column) {
                $field = [];
                $field["column_id"] = $column->id;
                $field["column_nullable"] = $column->nullable;

                if ($this->relationalDataTable[$step->id][$column->id][WorkflowStep::DATA_KEY_TYPE] === WorkflowStep::DATA_TYPE_INPUT) {
                    $field["target_row_id"] = $data["step-".$step->id."-relational-".$column->id];
                } else if ($this->relationalDataTable[$step->id][$column->id][WorkflowStep::DATA_KEY_TYPE] === WorkflowStep::DATA_TYPE_FILL_ID) {
                    $field["target_row_id"] = $ids[$this->relationalDataTable[$step->id][$column->id][WorkflowStep::DATA_KEY_FILL_IN]];
                } else {
                    $field["target_row_id"] = null;
                }

                $relationalFields[] = $field;
            }

            $textualColumns = $this->textualColumnTable[$step->tableId];
            $textualFields = [];

            foreach ($textualColumns as $column) {
                $field = [];
                $field["column_id"] = $column->id;

                $type = $this->textualDataTable[$step->id][$column->id][WorkflowStep::DATA_KEY_TYPE];

                if ($type === WorkflowStep::DATA_TYPE_INPUT) {
                    $field["value"] = $data["step-".$step->id."-textual-".$column->id];
                } else if ($type === WorkflowStep::DATA_TYPE_DEPENDING) {
                    if ($data["step-".$step->id."-textual-".$this->textualDataTable[$step->id][$column->id][WorkflowStep::DATA_KEY_DEPENDING_FIELD]] === $this->textualDataTable[$step->id][$column->id][WorkflowStep::DATA_KEY_DEPENDING_VALUE]) {
                        $field["value"] = $data["step-".$step->id."-textual-".$column->id];
                    } else {
                        $field["value"] = null;
                    }
                } else if ($type === WorkflowStep::DATA_TYPE_STATIC) {
                    $field["value"] = $this->textualDataTable[$step->id][$column->id][WorkflowStep::DATA_KEY_STATIC_VALUE];
                } else {
                    $field["value"] = null;
                }

                $validator = $column->getValidationType()->buildValidator();
                $field["valid"] = $validator->validate($field["value"]);

                $textualFields[] = $field;
            }

            // Database insertion

            $id = Row::create($step->tableId, Application::$instance->user->id, $data["step-$step->id-comment"], $data["step-$step->id-flag"], $data["step-$step->id-assignee"]);
            $ids[$step->id] = $id;

            if (count($relationalFields) > 0) {
                // Validity may be set incorrectly when referencing same row
                // Referencing same row may not be possible on insertion, but maybe when editing?
                RelationalField::createAll($id, $relationalFields);
            }

            if (count($textualFields) > 0) {
                TextualField::createAll($id, $textualFields);
            }

            Row::revalidate($id);
        }

        // Log execution

        WorkflowExecution::create($this->workflowId, Application::$instance->user->id, $ids);

        // Next

        Application::$instance->redirect("projects/".ProjectsController::$projectId."/workflows", "inserted");
    }
}
