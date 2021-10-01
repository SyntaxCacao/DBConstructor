<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\Projects\Tables\Insert;

use DBConstructor\Application;
use DBConstructor\Forms\Fields\MarkdownField;
use DBConstructor\Forms\Fields\SelectField;
use DBConstructor\Forms\Fields\TextareaField;
use DBConstructor\Forms\Fields\TextField;
use DBConstructor\Forms\Form;
use DBConstructor\Models\RelationalColumn;
use DBConstructor\Models\RelationalField;
use DBConstructor\Models\Row;
use DBConstructor\Models\TextualColumn;
use DBConstructor\Models\TextualField;
use DBConstructor\Util\JsonException;
use DBConstructor\Validation\Types\BooleanType;
use DBConstructor\Validation\Types\IntegerType;
use DBConstructor\Validation\Types\SelectionType;
use DBConstructor\Validation\Types\TextType;
use Exception;

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
     * @throws Exception
     * @throws JsonException
     */
    public function init(string $projectId, string $tableId, array $relationalColumns, array $textualColumns)
    {
        $this->projectId = $projectId;
        $this->tableId = $tableId;
        $this->relationalColumns = $relationalColumns;
        $this->textualColumns = $textualColumns;

        foreach ($relationalColumns as $column) {
            $field = new SelectField("relational-".$column->id, $column->label);
            $field->addOption("null", "Keine Auswahl");

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
            $fieldName = "textual-".$column->id;

            if ($column->type == TextualColumn::TYPE_TEXT) {
                /** @var TextType $type */
                $type = $column->getValidationType();

                if ($type->fieldType == TextType::FIELD_INPUT_DEFAULT || $type->fieldType == TextType::FIELD_INPUT_BLOCK) {
                    $field = new TextField($fieldName, $column->label);
                    $field->maxLength = 10000; // TODO: Check

                    if ($type->fieldType == TextType::FIELD_INPUT_BLOCK) {
                        $field->expand = true;
                    }
                } else {
                    if ($type->markdown == TextType::MARKDOWN_DISABLED) {
                        $field = new TextareaField($fieldName, $column->label);
                    } else {
                        $field = new MarkdownField($fieldName, $column->label);
                    }

                    $field->maxLength = 10000; // TODO: Check

                    if ($type->fieldType == TextType::FIELD_TEXTAREA_LARGE) {
                        $field->larger = true;
                    }
                }
            } else if ($column->type == TextualColumn::TYPE_SELECTION) {
                /** @var SelectionType $type */
                $type = $column->getValidationType();
                $field = new SelectField($fieldName, $column->label);
                // TODO: Prevent collision with option name
                $field->addOption("__null__", "Keine Auswahl");

                foreach ($type->options as $name => $label) {
                    $field->addOption($name, $label);
                }
            } else if ($column->type == TextualColumn::TYPE_DATE) {
                $field = new TextField($fieldName, $column->label);
                $field->maxLength = 10000; // TODO: Check
            } else if ($column->type == TextualColumn::TYPE_INTEGER) {
                /** @var IntegerType $type */
                $field = new TextField($fieldName, $column->label);
                $field->maxLength = 10000; // TODO Check
            } else if ($column->type == TextualColumn::TYPE_BOOLEAN) {
                /** @var BooleanType $type */
                $type = $column->getValidationType();
                $field = new SelectField($fieldName, $column->label);
                $field->addOption("null", "Keine Auswahl");

                if (is_null($type->falseLabel)) {
                    $field->addOption(BooleanType::VALUE_FALSE, "false");
                } else {
                    $field->addOption(BooleanType::VALUE_FALSE, $type->falseLabel);
                }

                if (is_null($type->trueLabel)) {
                    $field->addOption(BooleanType::VALUE_TRUE, "true");
                } else {
                    $field->addOption(BooleanType::VALUE_TRUE, $type->trueLabel);
                }
            } else {
                throw new Exception("Unsupported column type: ".$column->type);
            }

            $field->description = $column->getTypeLabel();
            $field->required = false;
            $this->addField($field);
        }

        /* TODO: Auskommentieren
        $field = new SelectField("next", "Als nächstes");
        $field->addOption("show", "Neuen Datensatz anzeigen");
        $field->addOption("new", "Weiteren Datensatz anlegen");

        $this->addField($field);
        */
    }

    /**
     * @throws JsonException
     */
    public function perform(array $data)
    {
        // TODO: step 1: check validity and build array; step 2: insert in database
        $rowId = Row::create($this->tableId, Application::$instance->user->id);
        $rowValid = true;

        if (count($this->relationalColumns) > 0) {
            $fields = [];

            foreach ($this->relationalColumns as $column) {
                $field = [];
                $field["column_id"] = $column->id;

                if ($data["relational-".$column->id] == "null") {
                    $field["target_row_id"] = null;
                } else {
                    $field["target_row_id"] = $data["relational-".$column->id];
                }

                // TODO: Include target row validity in validation check
                $field["valid"] = $field["target_row_id"] !== null || $column->nullable;

                if (! $field["valid"]) {
                    $rowValid = false;
                }

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

                if ($column->type == TextualColumn::TYPE_SELECTION) {
                    if ($field["value"] == "__null__") {
                        $field["value"] = null;
                    }
                } else if ($column->type == TextualColumn::TYPE_BOOLEAN) {
                    if ($field["value"] == "null") {
                        $field["value"] = null;
                    }
                }

                // TODO: Validator is built for each iteration, build all validators iteration instead
                // TODO: Check if calling the same Validator instance multiple times with different
                //   values actually works beforehand
                $validator = $column->getValidationType()->buildValidator();
                $field["valid"] = $validator->validate($field["value"]);
                //var_dump($field["valid"]);

                if (! $field["valid"]) {
                    $rowValid = false;
                }

                $fields[] = $field;
            }

            TextualField::createAll($rowId, $fields);
        }

        Row::setValidity($rowId, $rowValid);

        //if ($data["next"] == "show") {
            // TODO: Auskommentieren
            //Application::$instance->redirect("rows/$rowId", "created");
            Application::$instance->redirect("projects/$this->projectId/tables/$this->tableId/preview");
        //}
    }
}
