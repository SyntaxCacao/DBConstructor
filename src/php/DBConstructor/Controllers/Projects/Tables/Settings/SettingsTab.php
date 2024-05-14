<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\Projects\Tables\Settings;

use DBConstructor\Application;
use DBConstructor\Controllers\NotFoundController;
use DBConstructor\Controllers\Projects\Tables\TableForm;
use DBConstructor\Controllers\TabController;
use DBConstructor\Models\RelationalColumn;
use DBConstructor\Models\Row;
use DBConstructor\Models\WorkflowStep;

class SettingsTab extends TabController
{
    public function __construct()
    {
        parent::__construct("Einstellungen", "settings", "gear", true);
    }

    public function request(array $path, array &$data): bool
    {
        if (count($path) === 5) {
            $form = new TableForm();
            $form->init($data["table"]);
            $data["saved"] = $form->process();
            $data["form"] = $form;
            return true;
        }

        if (count($path) === 6 && $path[5] === "delete") {
            $data["rows"] = Row::count($data["table"]->id);
            $data["references"] = RelationalColumn::countTableReferencesFromOtherTables($data["table"]->id);
            $data["workflowSteps"] = WorkflowStep::countTableReferences($data["table"]->id);

            $data["allow"] = $data["rows"] === 0 && $data["references"] === 0 && $data["workflowSteps"] === 0;

            if ($data["allow"] && isset($_GET["delete"])) {
                $data["table"]->delete();
                Application::$instance->redirect("projects/".$data["project"]->id, "tableDeleted");
            }

            $data["tabpage"] = "delete";
            $data["title"] = "Tabelle löschen";
            return true;
        }

        (new NotFoundController())->request($path);
        return false;
    }
}
