<?php

declare(strict_types=1);

namespace DBConstructor\Controllers;

use DBConstructor\Application;
use DBConstructor\Controllers\Projects\Tables\TableGenerator;
use DBConstructor\Models\Participant;
use DBConstructor\Models\RelationalColumn;
use DBConstructor\Models\RelationalField;
use DBConstructor\Models\RowLoader;
use DBConstructor\Models\TextualColumn;
use DBConstructor\Models\TextualField;
use DBConstructor\Util\JsonException;
use DBConstructor\Util\MarkdownParser;

class XHRController extends Controller
{
    /**
     * @throws JsonException
     */
    public function request(array $path)
    {
        // validation
        if (count($path) === 2 && $path[1] === "validation") {
            $column = TextualColumn::load($_REQUEST["id"]);

            if ($column === null) {
                (new NotFoundController())->request($path);
                return;
            }

            $value = $_REQUEST["value"];

            if ($value === "") {
                $value = null;
            }

            $validator = $column->getValidationType()->buildValidator();
            $success = $validator->validate($value);
            echo $column->generateIndicator($validator, $success);
            return;
        }

        // markdown
        if (count($path) === 2 && $path[1] === "markdown") {
            echo MarkdownParser::parse($_REQUEST["src"]);
            return;
        }

        // selector
        if (count($path) === 3 && $path[1] === "selector") {
            if (intval($path[2]) === 0 || ! isset($_REQUEST["projectId"]) || intval($_REQUEST["projectId"]) === 0) {
                (new NotFoundController())->request($path);
                return;
            }

            $projectId = $_REQUEST["projectId"];
            $tableId = $path[2];

            if (Participant::loadFromUser($projectId, Application::$instance->user->id) === null) {
                (new ForbiddenController())->request($path);
                return;
            }

            $loader = new RowLoader($tableId);

            if (isset($_REQUEST["searchColumn"])) {
                $value = null;

                if (isset($_REQUEST["searchValue"])) {
                    $value = $_REQUEST["searchValue"];
                }

                $loader->addSearch($_REQUEST["searchColumn"], $value);
            }

            $page = 1;
            $rowCount = $loader->getRowCount();
            $pageCount = $loader->calcPages($rowCount);

            if (isset($_REQUEST["page"]) && intval($_REQUEST["page"]) > 1 && intval($_REQUEST["page"]) <= $pageCount) {
                $page = intval($_REQUEST["page"]);
            }

            $textualColumns = TextualColumn::loadList($tableId);
            $relationalColumns = RelationalColumn::loadList($tableId);

            echo '<form class="page-table-selector-modal-form">';
            echo '<select class="form-select page-table-selector-modal-form-column">';
            echo '<option value="">Feld für Suche auswählen...</option>';
            echo '<option value="id"'.(isset($_REQUEST["searchColumn"]) && $_REQUEST["searchColumn"] === "id" ? " selected" : "").'>ID</option>';
            echo '<option value="exportid"'.(isset($_REQUEST["searchColumn"]) && $_REQUEST["searchColumn"] === "exportid" ? " selected" : "").'>Letzte Export-ID</option>';

            foreach ($relationalColumns as $column) {
                $name = 'rel-'.$column->id;
                echo '<option value="'.$name.'"'.(isset($_REQUEST["searchColumn"]) && $_REQUEST["searchColumn"] === $name ? " selected" : "").'>'.htmlentities($column->label).'</option>';
            }

            foreach ($textualColumns as $column) {
                $name = 'txt-'.$column->id;
                echo '<option value="'.$name.'"'.(isset($_REQUEST["searchColumn"]) && $_REQUEST["searchColumn"] === $name ? " selected" : "").'>'.htmlentities($column->label).'</option>';
            }

            echo '</select>';

            echo '<input class="form-input page-table-selector-modal-form-value" maxlength="200" placeholder="Suchbegriff" type="text"'.(isset($_REQUEST["searchValue"]) ? ' value="'.htmlentities($_REQUEST["searchValue"]).'"' : '').'>';
            echo '<button class="button page-table-selector-modal-button" type="submit"><span class="bi bi-arrow-clockwise"></span>Aktualisieren</button>';
            echo '<input class="page-table-selector-modal-form-page" type="hidden" value="'.$page.'" data-page-count="'.$pageCount.'">';
            echo '</form>';

            $generator = new TableGenerator();
            $generator->rowCount = $rowCount;
            $generator->projectId = $projectId;
            $generator->tableId = $tableId;
            $generator->rows = $loader->getRows($page);
            $generator->textualColumns = $textualColumns;
            $generator->relationalColumns = $relationalColumns;

            $metaColumns = [TableGenerator::META_COLUMN_LAST_EDITED];

            if (isset($_REQUEST["searchColumn"]) && $_REQUEST["searchColumn"] === RowLoader::SEARCH_COLUMN_EXPORT_ID) {
                $metaColumns[] = TableGenerator::META_COLUMN_EXPORT_ID;
            }

            if (count($generator->rows) !== 0) {
                $generator->textualFields = TextualField::loadRows($generator->rows);
                $generator->relationalFields = RelationalField::loadRows($generator->rows);
            }

            $generator->generate(false, true, true, $metaColumns);
            return;
        }

        (new NotFoundController())->request($path);
    }
}
