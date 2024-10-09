<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\Projects\Exports;

use DBConstructor\Application;
use DBConstructor\Controllers\Projects\Tables\Structure\ColumnNameField;
use DBConstructor\Forms\Fields\CheckboxField;
use DBConstructor\Forms\Fields\SelectField;
use DBConstructor\Forms\Fields\TextareaField;
use DBConstructor\Forms\Fields\TextField;
use DBConstructor\Forms\Fields\ValidationClosure;
use DBConstructor\Forms\Form;
use DBConstructor\Models\Column;
use DBConstructor\Models\Export;
use DBConstructor\Models\Project;
use DBConstructor\Models\RelationalColumn;
use DBConstructor\Models\RelationalField;
use DBConstructor\Models\Row;
use DBConstructor\Models\RowAction;
use DBConstructor\Models\Table;
use DBConstructor\Models\TextualColumn;
use DBConstructor\Models\TextualField;
use DBConstructor\Validation\Types\SelectionType;
use Exception;
use ZipArchive;

class ExportForm extends Form
{
    const COMMENTS_FORMAT_JSON = "json";

    const COMMENTS_FORMAT_TEXT = "text";

    /** @var string|null */
    public $exportId;

    /** @var Project */
    public $project;

    public function __construct()
    {
        parent::__construct("export");
    }

    public function init(Project $project)
    {
        $this->project = $project;

        $field = new SelectField("format", "Format");
        $field->addOptions(Export::FORMATS);
        $this->addField($field);

        $field = new CheckboxField("internalid", "Interne ID mit ausgeben");
        $field->description = "Kann die Auffindbarkeit exportierter Datensätze auf dieser Plattform verbessern";
        $this->addField($field);

        $field = new CheckboxField("comments", "Kommentare mit ausgeben");
        $field->description = "In einer zusätzlichen Spalte werden die zu jedem Datensatz abgegebenen Kommentare ausgegeben";
        $this->addField($field);

        $field = new TextField("commentsColumnName", "Spaltenname");
        $field->dependsOn = "comments";
        $field->dependsOnValue = CheckboxField::VALUE;
        $field->defaultValue = "comments";
        // See ColumnNameField
        $field->maxLength = 64;
        $field->validationClosures[] = new ValidationClosure(static function ($value) {
            return ! in_array(strtolower($value), ColumnNameField::RESERVED_NAMES);
        }, "Der eingegebene Name ist reserviert", true);
        $field->validationClosures[] = new ValidationClosure(static function ($value) {
            return preg_match("/^[A-Za-z0-9-_]+$/D", $value);
        }, "Spaltennamen dürfen nur alphanumerische Zeichen, Bindestriche und Unterstriche enthalten.", true);
        $field->validationClosures[] = new ValidationClosure(function ($value) {
            return Column::isNameAvailableInProject($this->project->id, $value);
        }, "Dieser Spaltenname wird in diesem Projekt bereits verwendet");
        $this->addField($field);

        $field = new SelectField("commentsFormat", "Ausgabeformat");
        $field->dependsOn = "comments";
        $field->dependsOnValue = CheckboxField::VALUE;
        $field->addOption(self::COMMENTS_FORMAT_JSON, "JSON (maschinenlesbar)");
        $field->addOption(self::COMMENTS_FORMAT_TEXT, "Einfaches Textformat");
        $this->addField($field);

        $field = new CheckboxField("commentsAnonymize", "Verfasser anonymisieren");
        $field->description = "Es werden nur die numerischen IDs der Benutzer, nicht ihre Namen ausgegeben";
        $field->dependsOn = "comments";
        $field->dependsOnValue = CheckboxField::VALUE;
        $this->addField($field);

        $field = new CheckboxField("commentsExcludeAPI", "Über API eingefügte Kommentare auslassen");
        $field->description = "Automatisch über die API generierte Kommentare werden übergangen";
        $field->dependsOn = "comments";
        $field->dependsOnValue = CheckboxField::VALUE;
        $field->defaultValue = true;
        $this->addField($field);

        $field = new TextareaField("note", "Bemerkung");
        $field->required = false;
        $field->maxLength = 1000;
        $this->addField($field);

        $this->buttonLabel = "Exportieren";
    }

    /**
     * @throws Exception If the selected format is not implemented or an error occurs during exporting
     */
    public function perform(array $data)
    {
        if ($data["format"] == Export::FORMAT_CSV) {
            $tmpDir = "../tmp/exports/export-tmp-".uniqid("", true);

            if (! mkdir($tmpDir)) {
                throw new Exception("Could not create tmp dir");
            }

            $tables = Table::loadList($this->project->id);

            foreach ($tables as $table) {
                Row::setExportId($table->id);
            }

            $files = [];

            foreach ($tables as $table) {
                $tableFile = fopen("$tmpDir/$table->name.csv", "c");

                if (! $tableFile) {
                    throw new Exception("Could not create file for table $table->name");
                }

                $headings = ["id"];

                if ($data["internalid"]) {
                    $headings[] = "_intid";
                }

                // headings relational
                $relationalColumns = RelationalColumn::loadList($table->id);

                foreach ($relationalColumns as $column) {
                    $headings[] = $column->name;
                }

                // headings textual
                $textualColumns = TextualColumn::loadList($table->id);

                foreach ($textualColumns as $column) {
                    $headings[] = $column->name;
                }

                if ($data["comments"]) {
                    $headings[] = $data["commentsColumnName"];
                }

                fputcsv($tableFile, $headings);

                $rowsStatement = Row::selectListExport($table->id);
                $relationalFieldsStatement = RelationalField::selectTableExport($table->id);
                $textualFieldsStatement = TextualField::selectTableExport($table->id);

                if ($data["comments"]) {
                    $commentsStatement = RowAction::selectCommentsInTableForExport($table->id, $data["commentsAnonymize"], $data["commentsExcludeAPI"]);
                }

                $nextRelationalField = null;
                $nextTextualField = null;
                $nextComment = null;

                while ($rowData = $rowsStatement->fetch()) {
                    $row = new Row($rowData);
                    $rowExport = [$row->exportId];

                    if ($data["internalid"]) {
                        $rowExport[] = $row->id;
                    }

                    if (count($relationalColumns) > 0) {
                        // Fetch relational fields
                        $relationalFields = [];

                        while($nextRelationalField !== false) {
                            if ($nextRelationalField === null) {
                                $nextRelationalField = $relationalFieldsStatement->fetch();

                                if ($nextRelationalField === false) {
                                    break;
                                }

                                $nextRelationalField = new RelationalField($nextRelationalField);
                            }

                            if ($nextRelationalField->rowId === $row->id) {
                                $relationalFields[$nextRelationalField->columnId] = $nextRelationalField;
                                $nextRelationalField = null;
                            } else {
                                break;
                            }
                        }

                        // Add relational fields to export array
                        foreach ($relationalColumns as $column) {
                            if (isset($relationalFields[$column->id])) {
                                $rowExport[] = $relationalFields[$column->id]->targetRowExportId;
                            } else {
                                //echo "Table $table->id: Row $row->id: Missing value for relational column $column->id<br>";
                                $rowExport[] = "";
                            }
                        }
                    }

                    if (count($textualColumns) > 0) {
                        // Fetch textual fields
                        $textualFields = [];

                        while($nextTextualField !== false) {
                            if ($nextTextualField === null) {
                                $nextTextualField = $textualFieldsStatement->fetch();

                                if ($nextTextualField === false) {
                                    break;
                                }

                                $nextTextualField = new TextualField($nextTextualField);
                            }

                            if ($nextTextualField->rowId === $row->id) {
                                $textualFields[$nextTextualField->columnId] = $nextTextualField;
                                $nextTextualField = null;
                            } else {
                                break;
                            }
                        }

                        // Add textual fields to export array
                        foreach ($textualColumns as $column) {
                            if (isset($textualFields[$column->id])) {
                                $type = $column->getValidationType();
                                $value = $textualFields[$column->id]->value;

                                if ($type instanceof SelectionType && $type->allowMultiple) {
                                    $value = implode($type->separator, TextualColumn::decodeOptions($value) ?? []);
                                }

                                $rowExport[] = $value;
                            } else {
                                //echo "Table $table->id: Row $row->id: Missing value for textual column $column->id<br>";
                                $rowExport[] = "";
                            }
                        }
                    }

                    if ($data["comments"]) {
                        // Fetch comments
                        $comments = [];

                        while($nextComment !== false) {
                            if ($nextComment === null) {
                                $nextComment = $commentsStatement->fetch();

                                if ($nextComment === false) {
                                    break;
                                }

                                $nextComment = new RowAction($nextComment);
                            }

                            if ($nextComment->rowId === $row->id) {
                                $comments[] = $nextComment;
                                $nextComment = null;
                            } else {
                                break;
                            }
                        }

                        // Add comments to export array
                        if ($data["commentsFormat"] === self::COMMENTS_FORMAT_JSON) {
                            // JSON Format
                            $commentsExport = [];

                            foreach ($comments as $comment) {
                                if ($comment->isCommentExportExcluded()) continue;

                                $commentExport = [
                                    "user" => (int) $comment->userId,
                                    "time" => $comment->created,
                                    "text" => $comment->data[RowAction::COMMENT_DATA_TEXT]
                                ];

                                if (! $data["commentsAnonymize"]) {
                                    $commentExport["user"] = [
                                        "id" => (int) $comment->userId,
                                        "name" => "$comment->userFirstName $comment->userLastName"
                                    ];
                                }

                                $commentsExport[] = $commentExport;
                            }

                            if (count($commentsExport) > 0) {
                                $rowExport[] = json_encode($commentsExport);
                            } else {
                                $rowExport[] = "";
                            }
                        } else {
                            // Human-readable format
                            $commentsExport = "";

                            foreach ($comments as $comment) {
                                if ($data["commentsAnonymize"]) {
                                    $commentsExport .= "User #$comment->userId";
                                } else {
                                    $commentsExport .= "$comment->userFirstName $comment->userLastName (#$comment->userId)";
                                }

                                $commentsExport .= " on ".date("M j, Y \a\\t h:i A", strtotime($comment->created))."\n";
                                $commentsExport .= $comment->data[RowAction::COMMENT_DATA_TEXT]."\n\n";
                            }

                            $rowExport[] = rtrim($commentsExport, "\n");
                        }
                    }

                    // Write row to file
                    fputcsv($tableFile, $rowExport);
                }

                fclose($tableFile);
                $files[] = "$table->name.csv";

                /*
                while ($nextRelationalField !== false) {
                    echo "Table $table->id: Remaining rel field {$nextRelationalField['id']}<br>";
                    $nextRelationalField = $relationalFieldsStatement->fetch();
                }

                while ($nextTextualField !== false) {
                    echo "Table $table->id: Remaining text field {$nextTextualField['id']}<br>";
                    $nextTextualField = $textualFieldsStatement->fetch();
                }
                */

                // Close statements
                $rowsStatement = null;
                $relationalFieldsStatement = null;
                $textualFieldsStatement = null;
                $commentsStatement = null;
            }

            $this->exportId = Export::create($this->project->id, Application::$instance->user->id, $data["format"], $data["note"]);

            $zip = new ZipArchive();
            $zipName = Export::getLocalArchiveName($this->exportId);

            if (! $zip->open($zipName, ZipArchive::CREATE)) {
                throw new Exception("Failed to open $zipName");
            }

            foreach ($files as $file) {
                $zip->addFile("$tmpDir/$file", $file);
            }

            $zip->close();

            rename($tmpDir, Export::getLocalDirectoryName($this->exportId));
        } else {
            throw new Exception("Unimplemented export format: ".$data["format"]);
        }
    }
}
