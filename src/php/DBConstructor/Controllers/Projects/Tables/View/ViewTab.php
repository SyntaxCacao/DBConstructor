<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\Projects\Tables\View;

use DBConstructor\Controllers\NotFoundController;
use DBConstructor\Controllers\TabController;
use DBConstructor\Models\RelationalColumn;
use DBConstructor\Models\RelationalField;
use DBConstructor\Models\Row;
use DBConstructor\Models\TextualColumn;
use DBConstructor\Models\TextualField;

class ViewTab extends TabController
{
    public function __construct()
    {
        parent::__construct("Tabelle", "view", "table");
    }

    public function request(array $path, array &$data): bool
    {
        if (count($path) === 5) {
            $data["rows"] = Row::loadList($data["table"]->id);

            if (count($data["rows"]) == 0) {
                $data["tabpage"] = "blank";
                return true;
            }

            $data["relationalcolumns"] = RelationalColumn::loadList($data["table"]->id);
            $data["textualcolumns"] = TextualColumn::loadList($data["table"]->id);
            $data["relationalfields"] = RelationalField::loadTable($data["table"]->id);
            $data["textualfields"] = TextualField::loadTable($data["table"]->id);

            $data["notitle"] = true;

            return true;
        }

        if (count($path) === 6 && intval($path[5]) !== 0) {
            $row = Row::load($path[5]);

            if ($row === null || $row->tableId !== $data["table"]->id) {
                (new NotFoundController())->request($path);
                return false;
            }

            $data["relationalColumns"] = RelationalColumn::loadList($data["table"]->id);
            $data["textualColumns"] = TextualColumn::loadList($data["table"]->id);
            $data["row"] = $row;
            $data["relationalFields"] = RelationalField::loadRow($data["table"]->id);
            $data["textualFields"] = TextualField::loadRow($data["table"]->id);

            $data["tabpage"] = "row";
            $data["title"] = "#".$row->id;

            return true;
        }

        (new NotFoundController())->request($path);
        return false;
    }
}
