<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\Projects\Tables\View;

use DBConstructor\Application;
use DBConstructor\Controllers\Projects\Tables\RowForm;
use DBConstructor\Models\RelationalColumn;
use DBConstructor\Models\RelationalField;
use DBConstructor\Models\Row;
use DBConstructor\Models\TextualColumn;
use DBConstructor\Models\TextualField;
use DBConstructor\Util\JsonException;

class EditForm extends RowForm
{
    /** @var array<string, RelationalField> */
    public $relationalFields;

    /** @var Row */
    public $row;

    /** @var array<string, TextualField> */
    public $textualFields;

    public function __construct()
    {
        parent::__construct("table-view-edit", true);
    }

    /**
     * @param array<RelationalColumn> $relationalColumns
     * @param array<string, RelationalField> $relationalFields
     * @param array<TextualColumn> $textualColumns
     * @param array<string, TextualField> $textualFields
     * @throws JsonException
     */
    public function init(Row &$row, array $relationalColumns, array $relationalFields, array $textualColumns, array $textualFields)
    {
        $this->row = &$row;
        $this->relationalColumns = $relationalColumns;
        $this->relationalFields = $relationalFields;
        $this->textualColumns = $textualColumns;
        $this->textualFields = $textualFields;

        foreach ($relationalColumns as $column) {
            if (isset($relationalFields[$column->id])) {
                $this->addRelationalField($column, $relationalFields[$column->id]->targetRowId);
            } else {
                $this->addRelationalField($column);
            }
        }

        foreach ($textualColumns as $column) {
            if (isset($textualFields[$column->id])) {
                $this->addTextualField($column, $textualFields[$column->id]->value);
            } else {
                $this->addTextualField($column);
            }
        }
    }

    /**
     * @throws JsonException
     */
    public function perform(array $data)
    {
        foreach ($this->relationalColumns as $column) {
            if (! isset($this->relationalFields[$column->id])) {
                continue;
            }

            $field = $this->relationalFields[$column->id];

            if ($data["relational-".$column->id] !== $field->targetRowId) {
                $field->edit(Application::$instance->user->id, $this->row, $data["relational-".$column->id], $column->nullable);
            }
        }

        foreach ($this->textualColumns as $column) {
            if (! isset($this->textualFields[$column->id])) {
                continue;
            }

            $field = $this->textualFields[$column->id];

            if ($data["textual-".$column->id] !== $field->value) {
                $validator = $column->getValidationType()->buildValidator();
                $valid = $validator->validate($data["textual-".$column->id]);
                $field->edit(Application::$instance->user->id, $this->row, $data["textual-".$column->id], $valid);
            }
        }
    }
}
