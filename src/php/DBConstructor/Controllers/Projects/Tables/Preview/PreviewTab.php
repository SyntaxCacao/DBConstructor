<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\Projects\Tables\Preview;

use DBConstructor\Controllers\NotFoundController;
use DBConstructor\Controllers\TabController;
use DBConstructor\Models\RelationalColumn;
use DBConstructor\Models\RelationalField;
use DBConstructor\Models\Row;
use DBConstructor\Models\TextualColumn;
use DBConstructor\Models\TextualField;

class PreviewTab extends TabController
{
    public function __construct()
    {
        parent::__construct("Tabelle", "preview", "table");
    }

    public function request(array $path, array &$data): bool
    {
        if (count($path) != 5) {
            (new NotFoundController())->request($path);
            return false;
        }

        $data["relationalcolumns"] = RelationalColumn::loadList($data["table"]->id);
        $data["textualcolumns"] = TextualColumn::loadList($data["table"]->id);
        $data["rows"] = Row::loadList($data["table"]->id);
        $data["relationalfields"] = RelationalField::loadTable($data["table"]->id);
        $data["textualfields"] = TextualField::loadTable($data["table"]->id);

        $data["notitle"] = true;

        return true;
    }
}
