<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\Projects\Tables\Structure;

use DBConstructor\Application;
use DBConstructor\Forms\Fields\CheckboxField;
use DBConstructor\Forms\Fields\SelectField;
use DBConstructor\Forms\Form;
use DBConstructor\Models\RelationalColumn;
use DBConstructor\Models\RelationalField;
use DBConstructor\Models\Table;
use Exception;

class RelationalColumnForm extends Form
{
    /** @var RelationalColumn */
    public $column;

    /** @var string */
    public $projectId;

    /** @var bool */
    public $tableEmpty;

    /** @var string */
    public $tableId;

    public function __construct()
    {
        parent::__construct("relational-column-form");
    }

    /**
     * @param RelationalColumn|null $column null on creation
     */
    public function init(string $projectId, bool $manualOrder, string $tableId, string $tablePosition, bool $tableEmpty, RelationalColumn $column = null)
    {
        $this->projectId = $projectId;
        $this->tableId = $tableId;
        $this->tableEmpty = $tableEmpty;
        $this->column = $column;

        // label
        $this->addField(new ColumnLabelField($column));

        // name
        $this->addField(new ColumnNameField($tableId, $column));

        // target-table
        $field = new SelectField("target-table", "Zieltabelle");

        $tables = Table::loadList($this->projectId, $manualOrder, true);

        foreach ($tables as $table) {
            $field->addOption($table->id, $table->label." (".$table->name.")");
        }

        if (! is_null($column)) {
            $field->defaultValue = $column->targetTableId;
            $field->disabled = true;
        }

        $this->addField($field);

        // null-allowed
        $field = new CheckboxField("null-allowed", "Angabe ist optional");
        $field->description = "Wenn kein Wert angegeben wird, wird NULL gespeichert";

        if (! is_null($column)) {
            $field->defaultValue = $column->nullable;
        }

        $this->addField($field);

        // hide
        $this->addField(new ColumnHideField($column));

        // instructions
        $this->addField(new ColumnInstructionsField($column));

        // position
        $this->addField(new ColumnPositionField(RelationalColumn::loadList($tableId), $column));
    }

    /**
     * @throws Exception
     */
    public function perform(array $data)
    {
        if (is_null($this->column)) {
            // create
            $id = RelationalColumn::create($this->tableId, $data["target-table"], $data["name"], $data["label"], $data["instructions"], $data["position"], $data["null-allowed"], $data["hide"]);

            if (! $this->tableEmpty) {
                RelationalField::fill($this->tableId, $id, $data["null-allowed"]);
            }
        } else {
            // edit
            $nullAllowedChanged = $data["null-allowed"] != $this->column->nullable;
            $this->column->edit($data["name"], $data["label"], $data["instructions"], $data["null-allowed"], $data["hide"]);

            if ($this->column->position != $data["position"]) {
                $this->column->move(intval($data["position"]));
            }

            if ($nullAllowedChanged && ! $this->tableEmpty) {
                RelationalField::revalidateNullValues($this->column->id, $this->column->nullable);
            }
        }

        if (empty($_REQUEST["return"])) {
            Application::$instance->redirect("projects/$this->projectId/tables/$this->tableId", "saved");
        } else {
            header("Location: ".urldecode($_REQUEST["return"]));
            exit;
        }
    }
}
