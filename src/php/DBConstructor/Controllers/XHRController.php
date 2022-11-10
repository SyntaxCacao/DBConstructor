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
use DBConstructor\Util\JsonException;
use DBConstructor\Util\MarkdownParser;
use Exception;
use Throwable;

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

        // upload
        if ($path[1] === "upload") {
            // upload attachment
            if ($path[2] === "attachment" && count($path) === 4) {
                try {
                    $projectId = "";

                    if (intval($path[3]) === 0 || ($row = Row::loadWithProjectId($path[3], $projectId)) === null) {
                        (new NotFoundController())->request($path);
                        return;
                    }

                    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
                        http_response_code(405);
                        return;
                    }

                    if (! isset($_FILES["file"]) || ! isset($_FILES["file"]["name"]) || ! is_string($_FILES["file"]["name"]) || ! isset($_FILES["file"]["error"]) || ! isset($_FILES["file"]["tmp_name"])) {
                        // $_FILES["file"] won't be set if POST Content-Length is too large
                        // $_FILES["file"]["name"] won't be string if multiple files are sent
                        http_response_code(422);
                        header('Content-Type: application/json; charset=utf-8');
                        echo '{"message":"Die Datei konnte nicht hochgeladen werden. Möglicherweise ist sie zu groß."}';
                        return;
                    }

                    $fileName = $_FILES["file"]["name"];
                    $fileError = $_FILES["file"]["error"];

                    if ($fileError !== 0) {
                        $message = "Das Hochladen der Datei ist fehlgeschlagen.";

                        if ($fileError === UPLOAD_ERR_INI_SIZE || $fileError === UPLOAD_ERR_FORM_SIZE) {
                            $message = "Die Datei ist größer als die für den Webserver festgelegte Dateigrößengrenze.";
                        } else {
                            error_log("File upload inititiated by user with ID ".Application::$instance->user->id." failed with code ".$fileError);
                        }

                        http_response_code(422);
                        header('Content-Type: application/json; charset=utf-8');
                        echo '{"message":"'.$message.'"}';
                        return;
                    }

                    if (strlen($fileName) > 70) {
                        http_response_code(422);
                        header('Content-Type: application/json; charset=utf-8');
                        echo '{"message":"Der Dateiname ist zu lang (Erlaubt sind bis zu 70 Zeichen)."}';
                        return;
                    }

                    if (! preg_match("/^[a-zA-Z0-9_\-. ]+$/", $fileName)) {
                        http_response_code(422);
                        header('Content-Type: application/json; charset=utf-8');
                        echo '{"message":"Der Dateiname enthält ungültige Zeichen (Erlaubt: A-Z, a-z, 0-9, Bindestrich, Unterstrich, Leerzeichen, Punkt)."}';
                        return;
                    }

                    if (! RowAttachment::isNameAvailable($row->id, $fileName)) {
                        http_response_code(422);
                        header('Content-Type: application/json; charset=utf-8');
                        echo '{"message":"Eine Datei mit diesem Namen existiert bereits."}';
                        return;
                    }

                    Application::$instance->checkDir("tmp/attachments/".$projectId);
                    Application::$instance->checkDir("tmp/attachments/".$projectId."/tables/");
                    Application::$instance->checkDir("tmp/attachments/".$projectId."/tables/".$row->tableId);
                    Application::$instance->checkDir("tmp/attachments/".$projectId."/tables/".$row->tableId."/".$row->id);

                    $attachmentId = RowAttachment::create($row->id, Application::$instance->user, $fileName, filesize($_FILES["file"]["tmp_name"]));

                    if (! move_uploaded_file($_FILES["file"]["tmp_name"], RowAttachment::getPath($projectId, $row->tableId, $row->id, $attachmentId))) {
                        RowAttachment::delete($attachmentId);
                        throw new Exception("move_uploaded_file() returned false for upload of file named \"$fileName\" initiated by user with ID ".Application::$instance->user->id);
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
