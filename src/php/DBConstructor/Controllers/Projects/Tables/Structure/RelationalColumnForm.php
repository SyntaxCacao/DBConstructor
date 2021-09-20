<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\Projects\Tables\Structure;

use DBConstructor\Application;
use DBConstructor\Forms\Fields\CheckboxField;
use DBConstructor\Forms\Fields\MarkdownField;
use DBConstructor\Forms\Fields\SelectField;
use DBConstructor\Forms\Fields\TextField;
use DBConstructor\Forms\Fields\ValidationClosure;
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
        $field = new TextField("label", "Bezeichnung");
        $field->minLength = 3;
        $field->maxLength = 30;

        if (! is_null($column)) {
            $field->defaultValue = $column->label;
        }

        $this->addField($field);

        // name
        $field = new TextField("name", "Technischer Name");
        $field->maxLength = 30;
        $field->monospace = true;

        $field->validationClosures[] = new ValidationClosure(static function ($value) {
            return strtolower($value) != "id";
        }, 'Der Name "id" ist reserviert.', true);
        $field->validationClosures[] = new ValidationClosure(static function ($value) {
            return preg_match("/^[A-Za-z0-9-_]+$/", $value);
        }, "Spaltennamen dürfen nur alphanumerische Zeichen, Bindestriche und Unterstriche enthalten.", true);

        if (is_null($column)) {
            $field->validationClosures[] = new ValidationClosure(function ($value) {
                return RelationalColumn::isNameAvailable($this->tableId, $value);
            }, "Die Tabelle enthält bereits eine Spalte mit diesem Namen.");
        } else {
            $field->validationClosures[] = new ValidationClosure(function ($value) {
                return $value == $this->column->name || RelationalColumn::isNameAvailable($this->tableId, $value);
            }, "Die Tabelle enthält bereits eine Spalte mit diesem Namen.");

            $field->defaultValue = $column->name;
        }

        $this->addField($field);

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
            //var_dump($field->defaultValue);exit;
        }

        $this->addField($field);

        // description
        $field = new MarkdownField("description", "Erläuterung");
        $field->larger = false;
        $field->maxLength = 1000;
        $field->required = false;

        if (! is_null($column)) {
            $field->defaultValue = $column->description;
        }

        $this->addField($field);
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
