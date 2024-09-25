<?php

declare(strict_types=1);

namespace DBConstructor\Controllers;

use DBConstructor\Application;
use DBConstructor\Controllers\Projects\Tables\TableGenerator;
use DBConstructor\Models\Participant;
use DBConstructor\Models\RelationalColumn;
use DBConstructor\Models\RelationalField;
use DBConstructor\Models\Row;
use DBConstructor\Models\RowAttachment;
use DBConstructor\Models\RowLoader;
use DBConstructor\Models\TextualColumn;
use DBConstructor\Models\TextualField;
use DBConstructor\Util\MarkdownParser;
use DBConstructor\Validation\Types\SelectionType;
use Throwable;

class XHRController extends Controller
{
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

            $type = $column->getValidationType();

            if ($type instanceof SelectionType && $type->allowMultiple) {
                $value = TextualColumn::decodeOptions($value);
            }

            $validator = $type->buildValidator();
            $success = $validator->validate($value);
            echo $column->generateIndicator($validator, $success);
            return;
        }

        // markdown
        if (count($path) === 2 && $path[1] === "markdown") {
            echo MarkdownParser::parse($_REQUEST["src"]);
            return;
        }

        // upload
        if ($path[1] === "upload") {
            // upload attachment
            if ($path[2] === "attachment" && count($path) === 4) {
                try {
                    if (intval($path[3]) === 0 || ($row = Row::loadWithProjectId(Application::$instance->user->id, $path[3], $projectId, $isParticipant)) === null) {
                        (new NotFoundController())->request($path);
                        return;
                    }

                    if (! $isParticipant) {
                        (new ForbiddenController())->request($path);
                        return;
                    }

                    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
                        http_response_code(405);
                        return;
                    }

                    $result = RowAttachment::handleUpload($projectId, $row);

                    if ($result !== RowAttachment::UPLOAD_OK) {
                        http_response_code(422);
                        header("Content-Type: application/json; charset=utf-8");

                        switch ($result) {
                            case RowAttachment::UPLOAD_ERROR_FILE_TOO_LARGE:
                                echo '{"message":"Die Datei ist größer als die für den Webserver festgelegte Dateigrößengrenze."}';
                                break;
                            case RowAttachment::UPLOAD_ERROR_GENERIC:
                                echo '{"message":"Das Hochladen der Datei ist fehlgeschlagen."}';
                                break;
                            case RowAttachment::UPLOAD_ERROR_NAME_INVALID_CHARS:
                                echo '{"message":"Der Dateiname enthält ungültige Zeichen (Erlaubt: A-Z, a-z, 0-9, Bindestrich, Unterstrich, Leerzeichen, Punkt)."}';
                                break;
                            case RowAttachment::UPLOAD_ERROR_NAME_TAKEN:
                                echo '{"message":"Eine Datei mit diesem Namen existiert bereits."}';
                                break;
                            case RowAttachment::UPLOAD_ERROR_NAME_TOO_LONG:
                                echo '{"message":"Der Dateiname ist zu lang (Erlaubt sind bis zu 70 Zeichen)."}';
                                break;
                            case RowAttachment::UPLOAD_ERROR_NO_FILE:
                                echo '{"message":"Die Datei konnte nicht hochgeladen werden. Möglicherweise ist sie zu groß."}';
                                break;
                        }
                    }
                } catch (Throwable $throwable) {
                    error_log("File upload caused ".get_class($throwable)." in ".$throwable->getFile()." on line ".$throwable->getLine().": ".$throwable->getMessage()." – while processing ".$_SERVER["REQUEST_METHOD"]." ".$_SERVER["REQUEST_URI"]);
                    http_response_code(500);

                    if (Application::$instance->config["development"]) {
                        var_dump($throwable);
                    }
                }

                return;
            }
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
