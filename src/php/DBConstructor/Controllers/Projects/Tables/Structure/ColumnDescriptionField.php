<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\Projects\Tables\Structure;

use DBConstructor\Forms\Fields\MarkdownField;
use DBConstructor\Models\Column;

class ColumnDescriptionField extends MarkdownField
{
    public function __construct(Column $column = null)
    {
        parent::__construct("description", "Erläuterung");
        $this->description = "Wird bei der Eingabe von Daten angezeigt";
        $this->larger = false;
        $this->maxLength = 20000;
        $this->required = false;

        if (! is_null($column)) {
            $this->defaultValue = $column->description;
        }
    }
}
