<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\Projects\Tables\View;

use DBConstructor\Application;
use DBConstructor\Controllers\ForbiddenController;
use DBConstructor\Controllers\NotFoundController;
use DBConstructor\Controllers\Projects\Tables\TableGenerator;
use DBConstructor\Controllers\TabController;
use DBConstructor\Models\Participant;
use DBConstructor\Models\RelationalColumn;
use DBConstructor\Models\RelationalField;
use DBConstructor\Models\Row;
use DBConstructor\Models\RowAction;
use DBConstructor\Models\RowAttachment;
use DBConstructor\Models\Table;
use DBConstructor\Models\TextualColumn;
use DBConstructor\Models\TextualField;
use Exception;

class ViewTab extends TabController
{
    public function __construct()
    {
        parent::__construct("Tabelle", "view", "table");
    }

    /**
     * @throws Exception
     */
    public function request(array $path, array &$data): bool
    {
        if (count($path) <= 5) {
            // table view

            $relationalColumns = RelationalColumn::loadList($data["table"]->id);
            $textualColumns = TextualColumn::loadList($data["table"]->id);

            if (count($relationalColumns) === 0 && count($textualColumns) === 0) {
                $data["tabpage"] = "blank";
                return true;
            }

            $data["notitle"] = true;

            $participants = Participant::loadList($data["project"]->id);

            // filter
            $filterForm = new FilterForm($data["table"]->id);
            $filterForm->init($participants, $relationalColumns, $textualColumns);
            $filterForm->process();
            $loader = $filterForm->loader;

            $data["filterForm"] = $filterForm;

            // count rows
            $data["rowCount"] = $loader->getRowCount();

            if ($data["rowCount"] === 0) {
                $data["generator"] = new TableGenerator();
                $data["generator"]->rows = [];
                return true;
            }

            // determine page
            $data["pageCount"] = $loader->calcPages($data["rowCount"]);

            $page = 1;

            if (isset($_GET["page"]) && intval($_GET["page"]) > 1) {
                $page = intval($_GET["page"]);
            }

            if ($page > $data["pageCount"]) {
                $data["generator"] = new TableGenerator();
                $data["generator"]->rows = [];
                return true;
            }

            $data["currentPage"] = $page;

            // prepare generator
            $generator = new TableGenerator();
            $generator->rowCount = $data["rowCount"];
            $generator->projectId = $data["project"]->id;
            $generator->tableId = $data["table"]->id;
            $generator->relationalColumns = $relationalColumns;
            $generator->textualColumns = $textualColumns;
            $generator->rows = $loader->getRows($page);

            if ($filterForm->showExportIdColumn) {
                $data["metaColumns"] = TableGenerator::META_COLUMNS_DEFAULT;
                $data["metaColumns"][] = TableGenerator::META_COLUMN_EXPORT_ID;
            }

            if (count($relationalColumns) > 0) {
                $generator->relationalFields = RelationalField::loadRows($generator->rows);
            } else {
                $generator->relationalFields = [];
            }

            if (count($textualColumns) > 0) {
                $generator->textualFields = TextualField::loadRows($generator->rows);
            } else {
                $generator->textualFields = [];
            }

            $data["generator"] = $generator;
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

        if (count($path) >= 7 && $path[6] === "references") {
            // references view

            $fields = RelationalField::loadReferencingFields($data["row"]->id, true);

            $data["tables"] = Table::loadList($data["project"]->id, $data["project"]->manualOrder, true);
            $data["references"] = [];
            $data["referencesCount"] = count($fields);

            foreach ($fields as $field) {
                $data["references"][$field->rowTableId][] = $field;
            }

            if ($data["referencesCount"] === 0) {
                if (count($path) === 7) {
                    $data["tabpage"] = "row_references_blank";
                    return true;
                }
            } else {
                if (count($path) === 7) {
                    $data["tabpage"] = "row_references";
                    return true;
                } else if (count($path) === 8 && $path[7] === "redirect") {
                    $data["form"] = new RedirectForm();
                    $data["form"]->init($fields, $data["row"]->id, $data["table"]->id);
                    $data["form"]->process();

                    $data["tabpage"] = "row_references_redirect";
                    return true;
                }
            }
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

        if (count($path) >= 7 && $path[6] === "attachments") {
            // attachments

            if (count($path) === 8 && $path[7] === "upload") {
                // upload
                $data["tabpage"] = "row_attachments_upload";
                $data["title"] = "Dateien hochladen";
                return true;
            }

            if (count($path) === 9 && $path[7] === "download" &&
                ($attachment = RowAttachment::loadFromName($data["row"]->id, $path[8])) !== null &&
                $attachment->rowId === $data["row"]->id) {
                // download

                $file = RowAttachment::getPath($data["project"]->id, $data["table"]->id, $data["row"]->id, $attachment->id);

                if (! file_exists($file)) {
                    throw new Exception("Download file for attachment with ID ".$attachment->id." not found (expected path: \"".$file."\")");
                }

                if (! is_readable($file)) {
                    throw new Exception("Download file for attachment with ID ".$attachment->id." is not readable (expected path: \"".$file."\")");
                }

                header("Content-Description: File Transfer");
                header("Content-Disposition: attachment; filename=\"$attachment->fileName\"");
                header("Content-Length: ".filesize($file));

                readfile($file);
                return false;
            }

            if (count($path) === 9 && $path[7] === "view" &&
                ($attachment = RowAttachment::loadFromName($data["row"]->id, $path[8])) !== null &&
                $attachment->rowId === $data["row"]->id &&
                $attachment->isViewable()) {
                // view

                $file = RowAttachment::getPath($data["project"]->id, $data["table"]->id, $data["row"]->id, $attachment->id);

                if ($attachment->type === "pdf") {
                    header("Content-Description: File Transfer");
                    header("Content-Disposition: inline; filename=\"$attachment->fileName\"");
                    header("Content-Length: ".filesize($file));
                    header("Content-Type: application/pdf");

                    readfile($file);
                    return false;
                }

                if ($attachment->type === "bmp" || $attachment->type === "gif" || $attachment->type === "jpg" || $attachment->type === "jpeg" || $attachment->type === "png") {
                    header("Content-Description: File Transfer");
                    header("Content-Disposition: inline; filename=\"$attachment->fileName\"");
                    header("Content-Length: ".filesize($file));

                    if ($attachment->type === "jpg") {
                        header("Content-Type: image/jpeg");
                    } else {
                        header("Content-Type: image/$attachment->type");
                    }

                    readfile($file);
                    return false;
                }

                if ($attachment->type === "html") {
                    readfile($file);
                    return false;
                }

                $data["attachment"] = $attachment;
                $data["file"] = $file;
                $data["tabpage"] = "row_attachments_view";
                $data["title"] = $attachment->fileName;

                if ($attachment->size > pow(1024, 2)) {
                    $data["viewType"] = "raw_toolarge";
                    return true;
                }

                if ($attachment->type === "csv" || $attachment->type === "md") {
                    $data["viewType"] = $attachment->type;
                    return true;
                }

                $data["viewType"] = "raw";
                return true;
            }
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
        } else if (isset($_GET["deletePerm"]) && $data["row"]->deleted && $data["isManager"]) {
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

        if (isset($_GET["deleteAttachment"]) && intval($_GET["deleteAttachment"]) > 0 &&
            ($attachment = RowAttachment::load($_GET["deleteAttachment"])) !== null &&
            $attachment->rowId === $data["row"]->id &&
            ($data["isManager"] || $attachment->uploaderId === Application::$instance->user->id)) {

            if (! unlink(RowAttachment::getPath($data["project"]->id, $data["table"]->id, $data["row"]->id, $attachment->id))) {
                throw new Exception("unlink() returned false when trying to delete attachment with ID".$attachment->id);
            }

            RowAttachment::delete($attachment->id);
        }

        $data["attachments"] = RowAttachment::loadAll($data["row"]->id);
        $data["actions"] = RowAction::loadAll($data["row"]->id, $data["filtered"]);
        $data["tabpage"] = "row";

        return true;
    }
}
