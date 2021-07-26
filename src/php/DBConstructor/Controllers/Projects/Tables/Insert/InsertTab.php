<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\Projects\Tables\Insert;

use DBConstructor\Controllers\NotFoundController;
use DBConstructor\Controllers\TabController;
use DBConstructor\Models\RelationalColumn;
use DBConstructor\Models\TextualColumn;

class InsertTab extends TabController
{
    public function __construct()
    {
        parent::__construct("Daten erfassen", "insert", "pencil");
    }

    public function request(array $path, &$data): bool
    {
        if (count($path) != 5) {
            (new NotFoundController())->request($path);
            return false;
        }

        $data["relationalcolumns"] = RelationalColumn::loadList($data["table"]->id);
        $data["textualcolumns"] = TextualColumn::loadList($data["table"]->id);

        $form = new InsertForm();
        $form->init($data["project"]->id, $data["table"]->id, $data["relationalcolumns"], $data["textualcolumns"]);
        $success = $form->process();

        if ($success) {
            // In diesem Fall soll ein neuer Datensatz eingegeben werden können,
            // dafür muss ein neues Formular generiert werden, damit nicht die
            // Werte des gerade angelegten Datensatzes in den Feldern stehen.
            $form = new InsertForm();
            $form->init($data["project"]->id, $data["table"]->id, $data["relationalcolumns"], $data["textualcolumns"]);
        }

        $data["form"] = $form;
        $data["success"] = $success;

        $data["title"] = "Datensatz anlegen";

        return true;
    }
}
