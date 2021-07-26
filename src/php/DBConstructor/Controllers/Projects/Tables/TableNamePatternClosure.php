<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\Projects\Tables;

use DBConstructor\Forms\Fields\ValidationClosure;

class TableNamePatternClosure extends ValidationClosure
{
    public function __construct()
    {
        parent::__construct(static function ($value) {
            return preg_match("/^[A-Za-z0-9-_]+$/", $value);
        }, "Tabellennamen dürfen nur alphanumerische Zeichen, Bindestriche und Unterstriche enthalten.", true);
    }
}
