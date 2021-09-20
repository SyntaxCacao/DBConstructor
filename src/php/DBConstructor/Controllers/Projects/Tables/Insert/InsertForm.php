<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\Projects\Tables\Insert;

use DBConstructor\Application;
use DBConstructor\Forms\Fields\SelectField;
use DBConstructor\Forms\Fields\TextField;
use DBConstructor\Forms\Form;
use DBConstructor\Models\RelationalColumn;
use DBConstructor\Models\RelationalField;
use DBConstructor\Models\Row;
use DBConstructor\Models\TextualColumn;
use DBConstructor\Models\TextualField;

class InsertForm extends Form
{
    /** @var string */
    public $projectId;

    /** @var string */
    public $tableId;

    /** @var TextualColumn[] */
    public $textualColumns;

    /** @var RelationalColumn[] */
    public $relationalColumns;

    public function __construct()
    {
        parent::__construct("table-insert");
    }

    /**
     * @param RelationalColumn[] $relationalColumns
     * @param TextualColumn[] $textualColumns
     */
    public function init(string $projectId, string $tableId, array $relationalColumns, array $textualColumns)
    {
        $this->projectId = $projectId;
        $this->tableId = $tableId;
        $this->relationalColumns = $relationalColumns;
        $this->textualColumns = $textualColumns;

        foreach ($relationalColumns as $column) {
            $field = new SelectField("relational-".$column->id, $column->label);

            // TODO: Anders machen!!!!!
            $rows = TextualField::loadTable($column->targetTableId);

            foreach ($rows as $id => $values) {
                $str = "";

                foreach ($values as $value) {
                    $str .= $value->value."; ";
                }

                $field->addOption((string) $id, $str);
            }

            $this->addField($field);
        }

        foreach ($textualColumns as $column) {
            $field = new TextField("textual-".$column->id, $column->label." (".$column->getTypeLabel().")");
            $field->maxLength = 10000;
            $field->required = false;
            $this->addField($field);
        }

        $field = new SelectField("next", "Als nächstes");
        $field->addOption("show", "Neuen Datensatz anzeigen");
        $field->addOption("new", "Weiteren Datensatz anlegen");
        $this->addField($field);
    }

    public function perform(array $data)
    {
        $rowId = Row::create($this->tableId, Application::$instance->user->id);
        $rowValid = true;

        if (count($this->relationalColumns) > 0) {
            $fields = [];

            foreach ($this->relationalColumns as $column) {
                $field = [];
                $field["column_id"] = $column->id;
                $field["target_row_id"] = $data["relational-".$column->id];

                $validationResult = $column->getValidator()->validate($data["relational-".$column->id]);

                if ($validationResult->valid) {
                    $validationResult = RelationalField::VALIDITY_VALID;
                } else {
                    // TODO
                    //var_dump($validationResult);exit;
                    $validationResult = RelationalField::VALIDITY_INVALID;
                    $rowValid = false;
                }

                $field["validity"] = $validationResult;

                $fields[] = $field;
            }

            RelationalField::createAll($rowId, $fields);
        }

        if (count($this->textualColumns) > 0) {
            $fields = [];

            foreach ($this->textualColumns as $column) {
                $field = [];
                $field["column_id"] = $column->id;
                $field["value"] = $data["textual-".$column->id];

                $validationResult = $column->getValidator()->validate($data["textual-".$column->id]);

                if ($validationResult->valid) {
                    $validationResult = TextualField::VALIDITY_VALID;
                } else {
                    $validationResult = TextualField::VALIDITY_INVALID;
                    $rowValid = false;
                }

                $field["validity"] = $validationResult;

                $fields[] = $field;
            }

            TextualField::createAll($rowId, $fields);
        }

        if ($rowValid) {
            Row::setValidity($rowId, TextualField::VALIDITY_VALID);
        } else {
            Row::setValidity($rowId, TextualField::VALIDITY_INVALID);
        }

        if ($data["next"] == "show") {
            // TODO: Auskommentieren
            //Application::$instance->redirect("rows/$rowId", "created");
            Application::$instance->redirect("projects/$this->projectId/tables/$this->tableId/preview");
        }
    }
}
