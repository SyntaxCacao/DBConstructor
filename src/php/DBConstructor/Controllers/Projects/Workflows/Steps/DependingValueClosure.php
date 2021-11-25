<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\Projects\Workflows\Steps;

use DBConstructor\Forms\Fields\ValidationClosure;
use DBConstructor\Models\TextualColumn;

class DependingValueClosure extends ValidationClosure
{
    /** @var array<TextualColumn> */
    public $columns;

    /** @var string */
    public $id;

    /**
     * @param array<TextualColumn> $columns
     */
    public function __construct(array $columns, string $id)
    {
        parent::__construct(function ($value) {
            // TODO unsatisfying solution
            if (! isset($_REQUEST["field-txt-$this->id-depending-field"])) {
                return false;
            }

            if (! array_key_exists($_REQUEST["field-txt-$this->id-depending-field"], $this->columns)) {
                return false;
            }

            $validator = $this->columns[$_REQUEST["field-txt-$this->id-depending-field"]]->getValidationType()->buildValidator();
            return $validator->validate($value);
        }, "Die Eingabe ist für das gewählte Feld nicht gültig");

        $this->columns = $columns;
        $this->id = $id;
    }
}
