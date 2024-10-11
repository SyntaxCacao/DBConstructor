<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\Projects\Exports;

use DBConstructor\Controllers\NotFoundController;
use DBConstructor\Controllers\TabController;
use DBConstructor\Models\Export;
use DBConstructor\Models\Row;

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
                $data["export"] = Export::load($form->exportId);
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

        if (count($path) >= 4 && preg_match("/^\d+$/D", $path[3]) === 1 &&
            ($data["export"] = Export::load($path[3])) !== null &&
            $data["export"]->projectId === $data["project"]->id &&
            Export::existsLocalDirectory($data["export"]->id)) {

            if (count($path) === 4) {
                $data["archiveExists"] = Export::existsLocalArchive($data["export"]->id);
                $data["directory"] = Export::getLocalDirectoryName($data["export"]->id);

                $data["tabpage"] = "view_dir";
                $data["title"] = "Export #{$data["export"]->id}";
                return true;
            }

            if (count($path) === 5 &&
                preg_match("/^[A-Za-z0-9-_]+\.csv$/D", $path[4]) !== null &&
                Export::existsLocalDirectory($data["export"]->id) &&
                ($data["fileName"] = $data["export"]->lookUpLocalFile($path[4])) !== null) {
                // View export file
                $data["currentPage"] = 1;
                $data["rowsPerPage"] = 500;

                if (isset($_REQUEST["page"]) && ctype_digit($_REQUEST["page"])) {
                    $data["currentPage"] = (int) $_REQUEST["page"];
                }

                $data["tabpage"] = "view_table";
                $data["title"] = $data["fileName"];
                return true;
            }
        }

        (new NotFoundController())->request($path);
        return false;
    }
}
