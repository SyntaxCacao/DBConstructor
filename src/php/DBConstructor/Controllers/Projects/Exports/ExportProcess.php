<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\Projects\Exports;

use DBConstructor\Application;
use DBConstructor\Models\Export;
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

class ExportProcess
{
    const COMMENTS_FORMAT_JSON = "json";

    const COMMENTS_FORMAT_TEXT = "text";

    /** @var bool */
    public $commentsAnonymize = false;

    /** @var string */
    public $commentsColumnName = "comments";

    /** @var bool */
    public $commentsExcludeAPI = true;

    /** @var string */
    public $commentsFormat = ExportProcess::COMMENTS_FORMAT_TEXT;

    /** @var bool */
    public $includeComments = false;

    /** @var bool */
    public $includeInternalIds = false;

    /** @var string|null */
    public $note;

    /** @var string */
    public $projectId;

    public function __construct(string $projectId)
    {
        $this->projectId = $projectId;
    }

    /**
     * @throws Exception
     */
    public function run(): Export
    {
        $uniquid = uniqid("", true);
        $tmpDir = Export::TMP_DIR_EXPORTS."/export-tmp-$uniquid";

        if (! mkdir($tmpDir)) {
            throw new Exception("Could not create tmp dir");
        }

        $tables = Table::loadList($this->projectId);

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

            if ($this->includeInternalIds) {
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

            if ($this->includeComments) {
                $headings[] = $this->commentsColumnName;
            }

            fputcsv($tableFile, $headings);

            $rowsStatement = Row::selectListExport($table->id);
            $relationalFieldsStatement = RelationalField::selectTableExport($table->id);
            $textualFieldsStatement = TextualField::selectTableExport($table->id);

            if ($this->includeComments) {
                $commentsStatement = RowAction::selectCommentsInTableForExport($table->id, $this->commentsAnonymize, $this->commentsExcludeAPI);
            }

            $nextRelationalField = null;
            $nextTextualField = null;
            $nextComment = null;

            while ($rowData = $rowsStatement->fetch()) {
                $row = new Row($rowData);
                $rowExport = [$row->exportId];

                if ($this->includeInternalIds) {
                    $rowExport[] = $row->id;
                }

                if (count($relationalColumns) > 0) {
                    // Fetch relational fields
                    $relationalFields = [];

                    while ($nextRelationalField !== false) {
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

                    while ($nextTextualField !== false) {
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

                if ($this->includeComments) {
                    // Fetch comments
                    $comments = [];

                    while ($nextComment !== false) {
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
                    if ($this->commentsFormat === ExportProcess::COMMENTS_FORMAT_JSON) {
                        // JSON Format
                        $commentsExport = [];

                        foreach ($comments as $comment) {
                            if ($comment->isCommentExportExcluded()) continue;

                            $commentExport = [
                                "user" => (int) $comment->userId,
                                "time" => $comment->created,
                                "text" => $comment->data[RowAction::COMMENT_DATA_TEXT]
                            ];

                            if (! $this->commentsAnonymize) {
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
                            if ($this->commentsAnonymize) {
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

        $zip = new ZipArchive();
        $tmpZipName = Export::TMP_DIR_EXPORTS."/export-$uniquid.zip";

        if (! $zip->open($tmpZipName, ZipArchive::CREATE)) {
            throw new Exception("Failed to open $tmpZipName");
        }

        foreach ($files as $file) {
            if (! $zip->addFile("$tmpDir/$file", $file)) {
                throw new Exception("File $file could not be added to ZIP archive");
            }
        }

        if (! $zip->close()) {
            throw new Exception("$tmpZipName could not be closed");
        }

        $exportId = Export::create($this->projectId, Application::$instance->user->id, Export::FORMAT_CSV, $this->note);
        $export = Export::load($exportId);

        if (! rename($tmpDir, $export->getLocalDirectoryPath())) {
            throw new Exception("Could not rename $tmpDir to ".$export->getLocalDirectoryPath());
        }

        if (! rename($tmpZipName, $export->getLocalArchivePath())) {
            throw new Exception("Could not rename $tmpZipName to ".$export->getLocalArchivePath());
        }

        return $export;
    }
}
