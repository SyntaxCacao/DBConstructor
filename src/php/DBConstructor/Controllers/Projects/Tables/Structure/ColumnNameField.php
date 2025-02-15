<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\Projects\Tables\Structure;

use DBConstructor\Forms\Fields\TextField;
use DBConstructor\Forms\Fields\ValidationClosure;
use DBConstructor\Models\Column;

class ColumnNameField extends TextField
{
    /** @var Column|null */
    public $column;

    /** @var string */
    public $tableId;

    public function __construct(string $tableId, Column $column = null)
    {
        parent::__construct("name", "Technischer Name");
        $this->column = $column;
        $this->tableId = $tableId;

        $this->description = "Verwendbar sind alphanumerische Zeichen, Bindestriche und Unterstriche";
        $this->maxLength = 64;
        $this->monospace = true;

        $this->validationClosures[] = new ValidationClosure(static function ($value) {
            return ! in_array(strtolower($value), Column::RESERVED_NAMES);
        }, "Der eingegebene Name ist reserviert.", true);
        $this->validationClosures[] = new ValidationClosure(static function ($value) {
            return preg_match("/^[A-Za-z0-9-_]+$/D", $value);
        }, "Spaltennamen dürfen nur alphanumerische Zeichen, Bindestriche und Unterstriche enthalten.", true);

        if (is_null($column)) {
            $this->validationClosures[] = new ValidationClosure(function ($value) {
                return Column::isNameAvailable($this->tableId, $value);
            }, "Die Tabelle enthält bereits eine Spalte mit diesem Namen.");
        } else {
            $this->validationClosures[] = new ValidationClosure(function ($value) {
                // using strtolower() for case-insensitive comparison
                return strtolower($value) === strtolower($this->column->name) || Column::isNameAvailable($this->tableId, $value);
            }, "Die Tabelle enthält bereits eine Spalte mit diesem Namen.");

            $this->defaultValue = $column->name;
        }
    }
}
