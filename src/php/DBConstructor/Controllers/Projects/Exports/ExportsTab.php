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

    public function request(array $path, &$data): bool
    {
        if (count($path) != 3) {
            (new NotFoundController())->request($path);
            return false;
        }

        $form = new ExportForm();
        $form->init($data["project"]);
        $success = $form->process();

        if ($success) {
            $form = new ExportForm();
            $form->init($data["project"]);
        }

        $data["exports"] = Export::loadList($data["project"]->id);
        $data["form"] = $form;
        $data["success"] = $success;

        return true;
    }
}
