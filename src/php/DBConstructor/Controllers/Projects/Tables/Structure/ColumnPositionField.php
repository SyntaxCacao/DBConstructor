<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\Projects\Tables\Structure;

use DBConstructor\Forms\Fields\SelectField;
use DBConstructor\Models\Column;

class ColumnPositionField extends SelectField
{
    /**
     * @param array<Column> $columns
     */
    public function __construct(array $columns, Column $column = null)
    {
        parent::__construct("position", "Feld einordnen");
        $this->addOption("1", "Als erstes Feld");

        foreach ($columns as $element) {
            if (is_null($column) || $column->id != $element->id) {
                if (is_null($column) || intval($element->position) < intval($column->position)) {
                    $newPosition = (string) (intval($element->position)+1);
                } else {
                    $newPosition = $element->position;
                }

                $this->addOption($newPosition, "Nach ".$element->label." (".$element->name.", ".$element->position.")");

                if (is_null($column) || $newPosition == $column->position) {
                    // before or: if not selected, last element should be default
                    // after or: select column before this column as default
                    $this->defaultValue = $newPosition;
                }
            }
        }
    }
}
