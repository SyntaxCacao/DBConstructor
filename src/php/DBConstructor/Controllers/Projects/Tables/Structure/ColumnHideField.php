<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\Projects\Tables\Structure;

use DBConstructor\Forms\Fields\CheckboxField;
use DBConstructor\Models\Column;

class ColumnHideField extends CheckboxField
{
    public function __construct(Column $column = null)
    {
        parent::__construct("hide", "Feld in der Tabellenvorschau ausblenden");
        $this->description = "Kann die Übersichtlichkeit verbessern, wirkt sich nicht auf den Export aus";

        if (! is_null($column)) {
            $this->defaultValue = $column->hide;
        }
    }
}
