<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\Projects\Exports;

use DBConstructor\Controllers\NotFoundController;
use DBConstructor\Controllers\TabController;
use DBConstructor\Models\Export;
use DBConstructor\Models\RelationalColumn;
use DBConstructor\Models\Row;
use DBConstructor\Models\Table;
use DBConstructor\Models\TextualColumn;

class ExportsTab extends TabController
{
    public function __construct()
    {
        parent::__construct("Export", "exports", "box-seam", true);
    }

    public function request(array $path, array &$data): bool
    {
        if (count($path) === 3) {
            if (isset($_REQUEST["delete"]) && ctype_digit($_REQUEST["delete"]) &&
                ($export = Export::load($_REQUEST["delete"])) !== null &&
                $export->projectId == $data["project"]->id) {
                $data["deleteSuccess"] = $export->delete();
            }

            // List exports
            $data["exports"] = Export::loadList($data["project"]->id);

            if (count($data["exports"]) > 0) {
                $data["tabpage"] = "list";
            } else {
                $data["tabpage"] = "blank";
            }

            return true;
        }

        if (count($path) === 4 && $path[3] === "run") {
            // Run export
            $form = new ExportForm();
            $form->init($data["project"]);
            $success = $form->process();

            if ($success) {
                $data["export"] = $form->export;
                $data["tabpage"] = "success";
                $data["title"] = "Export erfolgreich";
            } else {
                $data["form"] = $form;
                $data["validCount"] = Row::countValidInProject($data["project"]->id);
                $data["invalidCount"] = Row::countInvalidInProject($data["project"]->id);
                $data["tabpage"] = "form";
                $data["title"] = "Export durchführen";
            }

            return true;
        }

        if (count($path) === 4 && $path[3] === "docs") {
            // Show scheme docs

            $writer = new SchemeWriter();
            $writer->open();

            $tables = Table::loadList($data["project"]->id, $data["project"]->manualOrder, false, false, true);
            $writer->writeHead($data["project"], $tables);

            foreach ($tables as $table) {
                $writer->writeTableDocs($table,
                    RelationalColumn::loadList($table->id),
                    TextualColumn::loadList($table->id),
                    $table->exportableCount);
            }

            $writer->writeEnd();
            $writer->close();
            return false;
        }

        if (count($path) >= 4 && ctype_digit($path[3]) &&
            ($data["export"] = Export::load($path[3])) !== null &&
            $data["export"]->projectId === $data["project"]->id) {

            if (count($path) === 5 && $path[4] === "editnote") {
                // Edit note
                $data["editForm"] = new NoteEditForm();
                $data["editForm"]->init($data["export"]);
                $data["editForm"]->process();

                $data["tabpage"] = "form_note";
                $data["title"] = "Bemerkung bearbeiten";
                return true;
            }

            if (! $data["export"]->existsLocalDirectory()) {
                (new NotFoundController())->request($path);
                return false;
            }

            if (count($path) === 4) {
                // List export files
                $data["archiveExists"] = $data["export"]->existsLocalArchive();
                $data["directory"] = $data["export"]->getLocalDirectoryPath();

                $data["tabpage"] = "view_dir";
                $data["title"] = "Export #{$data["export"]->id}";
                return true;
            }

            if (count($path) === 5 && Export::isPossibleFileName($path[4]) &&
                ($data["fileName"] = $data["export"]->lookUpLocalFile($path[4])) !== null) {
                // View export file

                if (preg_match("/\.csv$/", $data["fileName"]) === 1) {
                    // CSV
                    $data["currentPage"] = 1;
                    $data["rowsPerPage"] = 500;

                    if (isset($_REQUEST["page"]) && ctype_digit($_REQUEST["page"])) {
                        $data["currentPage"] = (int) $_REQUEST["page"];
                    }

                    $data["tabpage"] = "view_table";
                    $data["title"] = $data["fileName"];
                    return true;
                } else if (preg_match("/\.html$/", $data["fileName"]) === 1) {
                    // HTML
                    readfile($data["export"]->getLocalDirectoryPath()."/".$data["fileName"]);
                    return false;
                }
            }
        }

        (new NotFoundController())->request($path);
        return false;
    }
}
