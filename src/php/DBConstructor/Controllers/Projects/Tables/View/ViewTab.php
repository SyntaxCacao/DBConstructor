<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\Projects\Tables\View;

use DBConstructor\Application;
use DBConstructor\Controllers\ForbiddenController;
use DBConstructor\Controllers\NotFoundController;
use DBConstructor\Controllers\TabController;
use DBConstructor\Models\Participant;
use DBConstructor\Models\RelationalColumn;
use DBConstructor\Models\RelationalField;
use DBConstructor\Models\Row;
use DBConstructor\Models\RowAction;
use DBConstructor\Models\TextualColumn;
use DBConstructor\Models\TextualField;
use DBConstructor\Util\JsonException;

class ViewTab extends TabController
{
    public function __construct()
    {
        parent::__construct("Tabelle", "view", "table");
    }

    /**
     * @throws JsonException
     */
    public function request(array $path, array &$data): bool
    {
        if (count($path) === 5) {
            // table view

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
            // dataset view

            $row = Row::load($path[5]);

            if ($row === null || $row->tableId !== $data["table"]->id) {
                (new NotFoundController())->request($path);
                return false;
            }

            $data["relationalColumns"] = RelationalColumn::loadList($data["table"]->id);
            $data["textualColumns"] = TextualColumn::loadList($data["table"]->id);
            $data["relationalFields"] = RelationalField::loadRow($row->id);
            $data["textualFields"] = TextualField::loadRow($row->id);

            if (isset($_GET["debug"])) {
                if (! Application::$instance->hasAdminPermissions()) {
                    (new ForbiddenController())->request($path);
                    return false;
                }

                $data["actions"] = RowAction::loadAll($row->id);
                $data["row"] = $row;
                $data["tabpage"] = "row_debug";
                $data["title"] = "#".$row->id;
                return true;
            }

            if (isset($_GET["flag"]) && ! $row->flagged) {
                $row->flag(Application::$instance->user->id);
            } else if (isset($_GET["unflag"]) && $row->flagged) {
                $row->unflag(Application::$instance->user->id);
            }

            if (isset($_GET["delete"]) && ! $row->deleted) {
                $row->delete(Application::$instance->user->id);
            } else if (isset($_GET["restore"]) && $row->deleted) {
                $row->restore(Application::$instance->user->id);
            }

            if (isset($_GET["deletePerm"]) && $row->deleted && $data["isManager"]) {
                $row->deletePermanently(Application::$instance->user->id);
                Application::$instance->redirect("projects/".$data["project"]->id."/tables/".$data["table"]->id."/view");
            }

            $editForm = new EditForm();
            $editForm->init($row, $data["relationalColumns"], $data["relationalFields"], $data["textualColumns"], $data["textualFields"]);
            $editForm->process();
            $data["editForm"] = $editForm;

            $commentForm = new CommentForm();
            $commentForm->init($row);
            if ($commentForm->process()) {
                // reset textarea after saving comment
                $commentForm = new CommentForm();
                $commentForm->init($row);
            }
            $data["commentForm"] = $commentForm;

            $participants = Participant::loadList($data["project"]->id);
            $assigneeForm = new AssigneeForm();
            $assigneeForm->init($row, $participants, $row->assigneeId);
            $assigneeForm->process();
            $data["assigneeForm"] = $assigneeForm;

            $data["actions"] = RowAction::loadAll($row->id);
            $data["row"] = $row;
            $data["tabpage"] = "row";
            $data["title"] = "#".$row->id;

            return true;
        }

        (new NotFoundController())->request($path);
        return false;
    }
}
