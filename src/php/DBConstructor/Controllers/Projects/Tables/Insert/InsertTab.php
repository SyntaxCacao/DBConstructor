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

    public function request(array $path, array &$data): bool
    {
        if (count($path) !== 5) {
            (new NotFoundController())->request($path);
            return false;
        }

        $relationalColumns = RelationalColumn::loadList($data["table"]->id);
        $textualColumns = TextualColumn::loadList($data["table"]->id);

        if (count($relationalColumns) === 0 && count($textualColumns) === 0) {
            $data["tabpage"] = "blank";
            return true;
        }

        $form = new InsertForm();
        $form->init($data["table"], $relationalColumns, $textualColumns);
        $success = $form->process();

        if ($success && isset($form->next) && $form->next == "new") {
            // In diesem Fall soll ein neuer Datensatz eingegeben werden können,
            // dafür muss ein neues Formular generiert werden, damit nicht die
            // Werte des gerade angelegten Datensatzes in den Feldern stehen.
            $form = new InsertForm();
            $form->init($data["table"], $relationalColumns, $textualColumns, true);
        }

        $data["form"] = $form;
        $data["success"] = $success;

        $data["title"] = "Datensatz anlegen";

        return true;
    }
}
