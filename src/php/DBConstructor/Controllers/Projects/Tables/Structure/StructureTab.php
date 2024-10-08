<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\Projects\Tables\Structure;

use DBConstructor\Application;
use DBConstructor\Controllers\ForbiddenController;
use DBConstructor\Controllers\NotFoundController;
use DBConstructor\Controllers\TabController;
use DBConstructor\Models\Project;
use DBConstructor\Models\RelationalColumn;
use DBConstructor\Models\Row;
use DBConstructor\Models\Table;
use DBConstructor\Models\TextualColumn;

class StructureTab extends TabController
{
    public function __construct()
    {
        parent::__construct("Struktur", "structure", "diagram-3");
    }

    /**
     * @param array{project: Project, table: Table} $data
     */
    public function request(array $path, array &$data): bool
    {
        if (count($path) <= 5) { // '<=' because this can be access with /projects/x/tables/x/ and /projects/x/tables/x/structure/
            $data["relationalColumns"] = RelationalColumn::loadList($data["table"]->id);
            $data["textualColumns"] = TextualColumn::loadList($data["table"]->id);
            $data["referencingColumns"] = RelationalColumn::loadReferencingColumns($data["table"]->id, $data["project"]->manualOrder);

            if (count($data["relationalColumns"]) == 0 && count($data["textualColumns"]) == 0) {
                $data["tabpage"] = "blank";
                return true;
            }

            $data["tabpage"] = "view";
            return true;
        }

        $data["isEmpty"] = Row::isTableEmpty($data["table"]->id);

        if (count($path) > 6 && $path[5] == "relational") {
            // relational

            if (count($path) == 7 && $path[6] == "create") {
                // create

                if (! $data["isManager"]) {
                    (new ForbiddenController())->request($path);
                    return false;
                }

                $form = new RelationalColumnForm();
                $form->init($data["project"]->id, $data["project"]->manualOrder, $data["table"]->id, $data["table"]->position, $data["isEmpty"]);
                $form->process();
                $data["form"] = $form;

                $data["heading"] = "Relationsfeld anlegen";
                $data["tabpage"] = "form";
                $data["title"] = "Feld anlegen";

                return true;
            }

            if (count($path) == 8 && intval($path[6]) != 0 && $path[7] == "edit") {
                // edit

                $column = RelationalColumn::load($path[6]);
                $data["column"] = $column;

                if (is_null($column) || $column->tableId != $data["table"]->id) {
                    (new NotFoundController())->request($path);
                    return false;
                }

                if (! $data["isManager"]) {
                    (new ForbiddenController())->request($path);
                    return false;
                }

                if (isset($_GET["delete"])) {
                    // delete
                    $column->delete();
                    Application::$instance->redirect("projects/".$data["project"]->id."/tables/".$data["table"]->id, "deleted");
                    exit;
                }

                $form = new RelationalColumnForm();
                $form->init($data["project"]->id, $data["project"]->manualOrder, $data["table"]->id, $data["table"]->position, $data["isEmpty"], $column);
                $form->process();
                $data["form"] = $form;

                $data["heading"] = "Relationsfeld bearbeiten";
                $data["tabpage"] = "form";
                $data["title"] = "Feld bearbeiten";

                return true;
            }
        } else if ($path[5] == "textual") {
            // textual

            if (count($path) == 7 && $path[6] == "create") {
                // create

                if (! $data["isManager"]) {
                    (new ForbiddenController())->request($path);
                    return false;
                }

                $form = new TextualColumnForm();
                $form->init($data["project"]->id, $data["table"]->id, $data["isEmpty"]);
                $form->process();
                $data["form"] = $form;

                $data["heading"] = "Wertfeld anlegen";
                $data["tabpage"] = "form";
                $data["title"] = "Feld anlegen";

                return true;
            }

            if (count($path) == 8 && intval($path[6]) != 0 && $path[7] == "edit") {
                // edit

                $column = TextualColumn::load($path[6]);
                $data["column"] = $column;

                if (is_null($column) || $column->tableId != $data["table"]->id) {
                    (new NotFoundController())->request($path);
                    return false;
                }

                if (! $data["isManager"]) {
                    (new ForbiddenController())->request($path);
                    return false;
                }

                if (isset($_GET["delete"])) {
                    // delete
                    $column->delete();
                    Application::$instance->redirect("projects/".$data["project"]->id."/tables/".$data["table"]->id, "deleted");
                    exit;
                }

                $form = new TextualColumnForm();
                $form->init($data["project"]->id, $data["table"]->id, $data["isEmpty"], $column);
                $form->process();
                $data["form"] = $form;

                $data["heading"] = "Wertfeld bearbeiten";
                $data["tabpage"] = "form";
                $data["title"] = "Feld bearbeiten";

                return true;
            }
        }

        (new NotFoundController())->request($path);
        return false;
    }
}
