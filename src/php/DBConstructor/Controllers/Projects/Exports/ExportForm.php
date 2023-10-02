<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\Projects\Exports;

use DBConstructor\Application;
use DBConstructor\Forms\Fields\CheckboxField;
use DBConstructor\Forms\Fields\SelectField;
use DBConstructor\Forms\Fields\TextareaField;
use DBConstructor\Forms\Form;
use DBConstructor\Models\Export;
use DBConstructor\Models\Project;
use DBConstructor\Models\RelationalColumn;
use DBConstructor\Models\RelationalField;
use DBConstructor\Models\Row;
use DBConstructor\Models\Table;
use DBConstructor\Models\TextualColumn;
use DBConstructor\Models\TextualField;
use DBConstructor\Validation\Types\SelectionType;
use Exception;
use ZipArchive;

class ExportForm extends Form
{
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

                $columnsArray = ["id"];

                if ($data["internalid"]) {
                    $columnsArray[] = "_intid";
                }

                // headings relational
                $relationalColumns = RelationalColumn::loadList($table->id);

                foreach ($relationalColumns as $column) {
                    $columnsArray[] = $column->name;
                }

                // headings textual
                $textualColumns = TextualColumn::loadList($table->id);

                foreach ($textualColumns as $column) {
                    $columnsArray[] = $column->name;
                }

                fputcsv($tableFile, $columnsArray);

                $rowsStatement = Row::selectListExport($table->id);
                $relationalFieldsStatement = RelationalField::selectTableExport($table->id);
                $textualFieldsStatement = TextualField::selectTableExport($table->id);

                $nextRelationalField = null;
                $nextTextualField = null;

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
            }

            $id = Export::create($this->project->id, Application::$instance->user->id, $data["format"], $data["note"]);

            $zip = new ZipArchive();
            $zipName = "../tmp/exports/export-$id.zip";

            if (! $zip->open($zipName, ZipArchive::CREATE)) {
                throw new Exception("Failed to open $zipName");
            }

            foreach ($files as $file) {
                $zip->addFile("$tmpDir/$file", $file);
            }

            $zip->close();

        } else {
            throw new Exception("Unimplemented export format: ".$data["format"]);
        }
    }
}
