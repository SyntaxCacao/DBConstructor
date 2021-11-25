<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\Projects\Workflows\Steps;

use DBConstructor\Forms\Fields\ValidationClosure;
use DBConstructor\Models\TextualColumn;

class StaticValueClosure extends ValidationClosure
{
    /** @var TextualColumn */
    public $column;

    public function __construct(TextualColumn $column)
    {
        parent::__construct(function ($value) {
            $validator = $this->column->getValidationType()->buildValidator();
            return $validator->validate($value);
        }, "Die Eingabe ist für dieses Feld nicht gültig");

        $this->column = $column;
    }
}
