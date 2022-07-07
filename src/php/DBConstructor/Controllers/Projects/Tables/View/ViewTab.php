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
use DBConstructor\Models\Table;
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
        if (count($path) <= 5) {
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

        // dataset view

        $data["row"] = Row::load($path[5]);

        if ($data["row"] === null || $data["row"]->tableId !== $data["table"]->id) {
            (new NotFoundController())->request($path);
            return false;
        }

        $data["relationalColumns"] = RelationalColumn::loadList($data["table"]->id);
        $data["textualColumns"] = TextualColumn::loadList($data["table"]->id);
        $data["relationalFields"] = RelationalField::loadRow($data["row"]->id);
        $data["textualFields"] = TextualField::loadRow($data["row"]->id);

        $data["title"] = "#".$data["row"]->id;

        if (count($path) === 7 && $path[6] === "raw") {
            // raw/debug view

            if (! $data["isManager"]) {
                (new ForbiddenController())->request($path);
                return false;
            }

            $data["actions"] = RowAction::loadAll($data["row"]->id);
            $data["tabpage"] = "row_raw";
            return true;
        }

        if (count($path) === 7 && $path[6] === "references") {
            // references view

            $fields = RelationalField::loadReferencingFields($data["row"]->id, true);

            $data["tables"] = Table::loadList($data["project"]->id, $data["project"]->manualOrder, true);
            $data["references"] = [];
            $data["referencesCount"] = count($fields);

            foreach ($fields as $field) {
                $data["references"][$field->rowTableId][] = $field;
            }

            if ($data["referencesCount"] === 0) {
                $data["tabpage"] = "row_references_blank";
            } else {
                $data["tabpage"] = "row_references";
            }

            return true;
        }

        if (count($path) === 7 && $path[6] === "revalidate") {
            // revalidation view

            if (! $data["isManager"]) {
                (new ForbiddenController())->request($path);
                return false;
            }

            $relFieldsValidBefore = [];
            $relFieldsValidAfter = [];

            foreach ($data["relationalFields"] as $field) {
                $relFieldsValidBefore["#".$field->id.", column #".$field->columnId." ".$data["relationalColumns"][$field->columnId]->name] = $field->valid;
                $field->revalidate($data["relationalColumns"][$field->columnId]->nullable, false);
                $relFieldsValidAfter["#".$field->id.", column #".$field->columnId." ".$data["relationalColumns"][$field->columnId]->name] = $field->valid;
            }

            $textFieldsValidBefore = [];
            $textFieldsValidAfter = [];

            foreach ($data["textualFields"] as $field) {
                $textFieldsValidBefore["#".$field->id.", column #".$field->columnId." ".$field->columnName] = $field->valid;
                $validator = $data["textualColumns"][$field->columnId]->getValidationType()->buildValidator();
                $field->setValid($validator->validate($field->value), false);
                $textFieldsValidAfter["#".$field->id.", column #".$field->columnId." ".$field->columnName] = $field->valid;
            }

            $rowValidBefore = $data["row"]->valid;
            Row::revalidate($data["row"]->id);
            $data["row"]->updateValidity();
            $rowValidAfter = $data["row"]->valid;

            $data["relFieldsValidBefore"] = $relFieldsValidBefore;
            $data["relFieldsValidAfter"] = $relFieldsValidAfter;
            $data["textFieldsValidBefore"] = $textFieldsValidBefore;
            $data["textFieldsValidAfter"] = $textFieldsValidAfter;
            $data["rowValidBefore"] = $rowValidBefore;
            $data["rowValidAfter"] = $rowValidAfter;

            $data["tabpage"] = "row_revalidate";
            return true;
        }

        if (count($path) !== 6) {
            (new NotFoundController())->request($path);
            return false;
        }

        // dataset view

        if (isset($_GET["flag"]) && ! $data["row"]->flagged) {
            $data["row"]->flag(Application::$instance->user->id);
        } else if (isset($_GET["unflag"]) && $data["row"]->flagged) {
            $data["row"]->unflag(Application::$instance->user->id);
        }

        if (isset($_GET["delete"]) && ! $data["row"]->deleted) {
            $data["row"]->delete(Application::$instance->user->id);
        } else if (isset($_GET["restore"]) && $data["row"]->deleted) {
            $data["row"]->restore(Application::$instance->user->id);
        } else if (isset($_GET["deletePerm"]) && $data["row"]->deleted && ($data["isManager"] || $data["row"]->creatorId === Application::$instance->user->id)) {
            $data["row"]->deletePermanently(Application::$instance->user->id);
            Application::$instance->redirect("projects/".$data["project"]->id."/tables/".$data["table"]->id."/view");
        }

        $editForm = new EditForm();
        $editForm->init($data["row"], $data["relationalColumns"], $data["relationalFields"], $data["textualColumns"], $data["textualFields"]);
        $editForm->process();
        $data["editForm"] = $editForm;

        $commentForm = new CommentForm();
        $commentForm->init($data["row"]);
        if ($commentForm->process()) {
            // reset textarea after saving comment
            $commentForm = new CommentForm();
            $commentForm->init($data["row"]);
        }
        $data["commentForm"] = $commentForm;

        $participants = Participant::loadList($data["project"]->id);
        $assigneeForm = new AssigneeForm();
        $assigneeForm->init($data["row"], $participants, $data["row"]->assigneeId);
        $assigneeForm->process();
        $data["assigneeForm"] = $assigneeForm;

        $data["filtered"] = isset($_REQUEST["filtered"]);

        $data["actions"] = RowAction::loadAll($data["row"]->id, $data["filtered"]);
        $data["tabpage"] = "row";

        return true;
    }
}
