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
use DBConstructor\Validation\Types\SelectionType;

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

    public function getRowId()
    {
        return $this->row->id;
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
                $value = $textualFields[$column->id]->value;
                $type = $column->getValidationType();

                if ($type instanceof SelectionType && $type->allowMultiple) {
                    $value = TextualColumn::decodeOptions($value);
                }

                $this->addTextualField($column, $value);
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
                $field->edit(Application::$instance->user->id, false, $this->row, $data["relational-".$column->id], $column->nullable);
            }
        }

        foreach ($this->textualColumns as $column) {
            if (! isset($this->textualFields[$column->id])) {
                continue;
            }

            $type = $column->getValidationType();
            $field = $this->textualFields[$column->id];
            $value = $data["textual-".$column->id];

            if ($type instanceof SelectionType && $type->allowMultiple) {
                $changed = ! TextualColumn::isEquivalent($value, TextualColumn::decodeOptions($field->value));
                $value = TextualColumn::encodeOptions($value);
            } else {
                $changed = $value !== $field->value;
            }

            if ($changed) {
                $validator = $type->buildValidator();
                $valid = $validator->validate($data["textual-".$column->id]); // not using $value here, because array must be used for multiple selection
                $field->edit(Application::$instance->user->id, false, $this->row, $value, $valid);
            }
        }
    }
}
