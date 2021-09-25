<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\Projects\Tables\Structure;

use DBConstructor\Forms\Fields\TextField;
use DBConstructor\Models\Column;

class ColumnLabelField extends TextField
{
    public function __construct(Column $column = null)
    {
        parent::__construct("label", "Bezeichnung");
        $this->minLength = 3;
        $this->maxLength = 30;

        if (! is_null($column)) {
            $this->defaultValue = $column->label;
        }
    }
}
