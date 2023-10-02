<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\Projects\Exports;

use DBConstructor\Controllers\NotFoundController;
use DBConstructor\Controllers\TabController;
use DBConstructor\Models\Export;

class ExportsTab extends TabController
{
    public function __construct() {
        parent::__construct("Export", "exports", "box-seam");
    }

    public function request(array $path, array &$data): bool
    {
        if (! $data["isManager"]) {
            $data["forbidden"] = true;
            return true;
        }

        if (count($path) === 3) {
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
                $data["tabpage"] = "form";
                $data["title"] = "Export durchführen";
            }

            return true;
        }

        (new NotFoundController())->request($path);
        return false;
    }
}
