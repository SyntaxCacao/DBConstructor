<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\Projects\Tables;

use DBConstructor\Application;
use DBConstructor\Controllers\ForbiddenController;
use DBConstructor\Controllers\NotFoundController;
use DBConstructor\Controllers\Projects\Tables\Insert\InsertTab;
use DBConstructor\Controllers\Projects\Tables\Issues\IssuesTab;
use DBConstructor\Controllers\Projects\Tables\Preview\PreviewTab;
use DBConstructor\Controllers\Projects\Tables\Settings\SettingsTab;
use DBConstructor\Controllers\Projects\Tables\Structure\StructureTab;
use DBConstructor\Controllers\TabController;
use DBConstructor\Controllers\TabRouter;
use DBConstructor\Models\Table;

class TablesTab extends TabController
{
    public function __construct() {
        parent::__construct("Übersicht", "tables", "signpost-split");
    }

    public function request(array $path, &$data): bool
    {
        if (count($path) <= 3) { // <= because this can be access with /projects/x/ and /projects/x/tables
            $data["tables"] = Table::loadList($data["project"]->id);
            $data["tabpage"] = "list";
            return true;
        }

        if (count($path) == 4 && $path[3] == "create") {
            if (! $data["isManager"]) {
                (new ForbiddenController())->request($path);
                return false;
            }

            $form = new TableForm();
            $form->init($data["project"]->id);
            $form->process();
            $data["form"] = $form;

            $data["tabpage"] = "create";
            $data["title"] = "Tabelle anlegen";
            return true;
        }

        // tables pages - identify table
        if (intval($path[3]) == 0) {
            // => not int
            // TODO: possibly redundant, string is inserted in Table::load anyway
            (new NotFoundController())->request($path);
            return false;
        }

        $table = Table::load($path[3]);
        $data["table"] = $table;

        if (is_null($table) || $table->projectId != $data["project"]->id) {
            (new NotFoundController())->request($path);
            return false;
        }

        $tabRouter = new TabRouter();
        $tabRouter->register(new StructureTab(), true);
        $tabRouter->register(new PreviewTab());
        $tabRouter->register(new InsertTab());
        $tabRouter->register(new IssuesTab());
        $tabRouter->register(new SettingsTab());

        if ($tabRouter->route($path, 4, $data)) {
            $data["table-tabs"] = $tabRouter;

            $data["page"] = "table";

            if (isset($data["title"])) {
                $data["title"] .= " · ".$table->label;
            } else if (isset($data["notitle"])) {
                $data["title"] = $table->label;
            } else {
                $data["title"] = $tabRouter->current->label." · ".$table->label;
            }

            Application::$instance->callTemplate($data);
        }

        return false;
    }
}
