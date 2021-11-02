<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\Projects\Tables;

use DBConstructor\Forms\Fields\Field;
use DBConstructor\Models\RelationalColumn;
use DBConstructor\Models\Row;
use DBConstructor\Models\TextualField;

class RelationalSelectField extends Field
{
    /** @var RelationalColumn */
    public $column;

    /** @var array<string, Row> */
    public $rows;

    /** @var array<string, array<string, TextualField>> */
    public $table;

    public function __construct(RelationalColumn $column)
    {
        parent::__construct("relational-".$column->id);
        $this->column = $column;
        $this->required = false;

        // TODO: Anders machen!!!!!
        $this->rows = Row::loadList($this->column->targetTableId);
        $this->table = TextualField::loadTable($this->column->targetTableId);
    }

    public function generateField(): string
    {
        $html = '<select class="form-select" name="field-'.htmlentities($this->name).'">';
        $html .= '<option value="">Keine Auswahl</option>';

        $selectedOptionIncluded = false;

        foreach ($this->table as $id => $fields) {
            if (! isset($this->rows[$id])) {
                // This is the case if row has been deleted
                // TODO Handle this differently
                continue;
            }

            $str = "";
            $first = true;

            foreach ($fields as $field) {
                if (! $first) {
                    $str .= "; ";
                }

                if ($field->value === null) {
                    $str .= "NULL";
                } else {
                    $str .= $field->value;
                }

                $first = false;
            }

            if (! $this->rows[$id]->valid) {
                $str .= " (ungültig)";
            }

            $html .= '<option value="'.$id.'" data-valid="'.var_export($this->rows[$id]->valid, true).'"';

            if ($this->value == $id) {
                $html .= ' selected';
                $selectedOptionIncluded = true;
            }

            $html .= '>'.htmlentities($str).'</option>';
        }

        if ($this->value !== null && ! $selectedOptionIncluded) {
            $html .= '<option value="'.htmlentities($this->value).'" data-valid="false" selected>Unzulässiger Wert: '.htmlentities($this->value).'</option>"';
        }

        $html .= '</select>';
        return $html;
    }

    public function generateGroup(array $errorMessages): string
    {
        return $this->generateField();
    }

    public function hasValue(): bool
    {
        return true;
    }

    public function validate(): array
    {
        if (! ($this->value === null || array_key_exists($this->value, $this->rows))) {
            return ["Wählen Sie eine Option"];
        }

        return [];
    }
}
