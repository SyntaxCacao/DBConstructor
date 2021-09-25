<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\Projects\Tables\Structure;

use DBConstructor\Application;
use DBConstructor\Forms\Fields\CheckboxField;
use DBConstructor\Forms\Fields\SelectField;
use DBConstructor\Forms\Form;
use DBConstructor\Models\RelationalColumn;
use DBConstructor\Models\Table;
use DBConstructor\Validation\Validator;
use Exception;

class RelationalColumnForm extends Form
{
    /** @var RelationalColumn */
    public $column;

    /** @var string */
    public $projectId;

    /** @var string */
    public $tableId;

    public function __construct()
    {
        parent::__construct("relational-column-form");
    }

    /**
     * @param RelationalColumn|null $column null on creation
     */
    public function init(string $projectId, string $tableId, string $tablePosition, RelationalColumn $column = null)
    {
        $this->projectId = $projectId;
        $this->tableId = $tableId;
        $this->column = $column;

        // label
        $this->addField(new ColumnLabelField($column));

        // name
        $this->addField(new ColumnNameField($tableId, $column));

        // target table
        $field = new SelectField("target-table", "Zieltabelle");

        $tables = Table::loadListAbove($this->projectId, $tablePosition);

        foreach ($tables as $table) {
            $field->addOption($table->id, $table->label." (".$table->name.")");
        }

        if (! is_null($column)) {
            $field->defaultValue = $column->targetTableId;
        }

        $this->addField($field);

        // rule-null-allowed
        $field = new CheckboxField("rule-null-allowed", "Angabe ist optional");
        $field->description = "Wenn kein Wert angegeben wird, wird NULL gespeichert.";

        if (! is_null($column)) {
            $field->defaultValue = $column->isOptional();
        }

        $this->addField($field);

        // description
        $this->addField(new ColumnDescriptionField($column));
    }

    /**
     * @throws Exception
     */
    public function perform(array $data)
    {
        $rules = Validator::createRelationValidator(! $data["rule-null-allowed"])->toJSON();

        if (is_null($this->column)) {
            // create
            RelationalColumn::create($this->tableId, $data["target-table"], $data["name"], $data["label"], $data["description"], $rules);
        } else {
            // edit
            $targetTableChanged = $data["target-table"] != $this->column->targetTableId;
            $this->column->edit($data["target-table"], $data["name"], $data["label"], $data["description"], $rules);

            if ($targetTableChanged) {
                // TODO: !!!
                $this->column->invalidateFields();
            }
        }

        Application::$instance->redirect("projects/$this->projectId/tables/$this->tableId", "saved");
    }
}
