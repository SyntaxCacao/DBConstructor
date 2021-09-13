<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\Projects\Tables\Structure;

use DBConstructor\Controllers\ForbiddenController;
use DBConstructor\Controllers\NotFoundController;
use DBConstructor\Controllers\TabController;
use DBConstructor\Models\RelationalColumn;
use DBConstructor\Models\TextualColumn;

class StructureTab extends TabController
{
    public function __construct()
    {
        parent::__construct("Struktur", "structure", "diagram-3");
    }

    public function request(array $path, &$data): bool
    {
        if (count($path) <= 5) { // '<=' because this can be access with /projects/x/tables/x/ and /projects/x/tables/x/structure/
            $data["relationalcolumns"] = RelationalColumn::loadList($data["table"]->id);
            $data["textualcolumns"] = TextualColumn::loadList($data["table"]->id);

            $data["tabpage"] = "view";

            return true;
        }

        if (count($path) == 6 && $path[5] == "create") {
            if (! $data["isManager"]) {
                (new ForbiddenController())->request($path);
                return false;
            }

            if (isset($_REQUEST["type"]) && $_REQUEST["type"] == "relational") {
                $form = new RelationalColumnCreateForm();
                $form->init($data["project"]->id, $data["table"]->id, $data["table"]->position);
                $form->process();
                $data["form"] = $form;

                $data["tabpage"] = "create";
                $data["title"] = "Spalte anlegen";

                return true;
            } else if (isset($_REQUEST["type"]) && $_REQUEST["type"] == "textual") {
                $form = new TextualColumnCreateForm();
                $form->init($data["project"]->id, $data["table"]->id);
                $form->process();
                $data["form"] = $form;

                $data["tabpage"] = "create";
                $data["title"] = "Spalte anlegen";

                return true;
            }
        }

        (new NotFoundController())->request($path);
        return false;
    }
}
