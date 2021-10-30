<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\Projects\Exports;

use DBConstructor\Application;
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
        // TODO: Refactor as generator
        // https://www.php.net/manual/en/language.generators.overview.php

        if ($data["format"] == Export::FORMAT_CSV) {
            $tmpDir = "../tmp/exports/export-tmp-".uniqid("", true);

            if (! mkdir($tmpDir)) {
                throw new Exception("Could not create tmp dir");
            }

            $files = [];
            $tables = Table::loadList($this->project->id);

            foreach ($tables as $table) {
                $table = $table["obj"];
                $tableFile = fopen("$tmpDir/$table->name.csv", "c");

                if (! $tableFile) {
                    throw new Exception("Could not create file for table $table->name");
                }

                $columnsArray = ["id"];

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

                Row::setExportId($table->id);
                $rows = Row::loadListExport($table->id);
                $relationalFields = RelationalField::loadTable($table->id);
                $textualFields = TextualField::loadTable($table->id);

                foreach ($rows as $row) {
                    $rowCsv = [$row->exportId];

                    /*
                    if (! isset($fields[$row->id])) {
                        continue;
                    }
                    */

                    foreach ($relationalColumns as $column) {
                        if (isset($relationalFields[$row->id]) && isset($relationalFields[$row->id][$column->id]) && ! is_null($relationalFields[$row->id][$column->id]->targetRowExportId)) {
                            $rowCsv[] = $relationalFields[$row->id][$column->id]->targetRowExportId;
                        } else {
                            $rowCsv[] = "";
                        }
                    }

                    foreach ($textualColumns as $column) {
                        if (isset($textualFields[$row->id]) && isset($textualFields[$row->id][$column->id]) && ! is_null($textualFields[$row->id][$column->id])) {
                            $rowCsv[] = $textualFields[$row->id][$column->id]->value;
                        } else {
                            $rowCsv[] = "";
                        }
                    }

                    fputcsv($tableFile, $rowCsv);
                }

                fclose($tableFile);
                $files[] = "$table->name.csv";
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
