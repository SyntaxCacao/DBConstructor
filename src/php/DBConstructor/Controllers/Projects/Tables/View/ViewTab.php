<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\Projects\Tables\View;

use DBConstructor\Controllers\NotFoundController;
use DBConstructor\Controllers\TabController;
use DBConstructor\Models\Participant;
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
            $data["relationalColumns"] = RelationalColumn::loadList($data["table"]->id);
            $data["textualColumns"] = TextualColumn::loadList($data["table"]->id);

            if (count($data["relationalColumns"]) === 0 && count($data["textualColumns"]) === 0) {
                $data["tabpage"] = "blank";
                return true;
            }

            $data["notitle"] = true;

            $participants = Participant::loadList($data["project"]->id);

            // filter
            $filterForm = new FilterForm();
            $filterForm->init($participants);
            $filterForm->process();

            $data["filterForm"] = $filterForm;

            // count rows
            $data["rowCount"] = Row::countRowsFiltered($data["table"]->id, $filterForm);

            if ($data["rowCount"] === 0) {
                return true;
            }

            // determine page
            $data["pageCount"] = Row::calcPages($data["rowCount"]);

            $page = 1;

            if (isset($_GET["page"]) && intval($_GET["page"]) > 0) {
                $page = intval($_GET["page"]);
            }

            if ($page > $data["pageCount"]) {
                $data["rowCount"] = 0;
                return true;
            }

            $data["currentPage"] = $page;

            // load rows
            $data["rows"] = Row::loadListFiltered($data["table"]->id, $filterForm, $page);

            // load fields
            if (count($data["relationalColumns"]) > 0) {
                $data["relationalFields"] = RelationalField::loadRows($data["rows"]);
            } else {
                $data["relationalFields"] = [];
            }

            if (count($data["textualColumns"]) > 0) {
                $data["textualFields"] = TextualField::loadRows($data["rows"]);
            } else {
                $data["textualFields"] = [];
            }

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
