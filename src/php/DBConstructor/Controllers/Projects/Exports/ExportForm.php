<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\Projects\Exports;

use DBConstructor\Forms\Fields\CheckboxField;
use DBConstructor\Forms\Fields\SelectField;
use DBConstructor\Forms\Fields\TextField;
use DBConstructor\Forms\Fields\ValidationClosure;
use DBConstructor\Forms\Form;
use DBConstructor\Models\Column;
use DBConstructor\Models\Export;
use DBConstructor\Models\Project;
use Exception;

class ExportForm extends Form
{
    /** @var Export|null */
    public $export;

    /** @var Project */
    public $project;

    /** @var string|null */
    public $internalIdColumnName;

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

        $field = new CheckboxField("internalId", "Interne ID mit ausgeben");
        $field->description = "Kann die Auffindbarkeit exportierter Datensätze auf dieser Plattform verbessern";
        $this->addField($field);

        $field = new TextField("internalIdColumnName", "Spaltenname");
        $field->dependsOn = "internalId";
        $field->dependsOnValue = CheckboxField::VALUE;
        // TODO: Disabled because CSV preview currently depends on this column having this name
        $field->disabled = true;
        $field->defaultValue = "_intid";
        // See ColumnNameField
        $field->maxLength = 64;
        $field->validationClosures[] = new ValidationClosure(static function ($value) {
            return ! in_array(strtolower($value), Column::RESERVED_NAMES);
        }, "Der eingegebene Name ist reserviert", true);
        $field->validationClosures[] = new ValidationClosure(static function ($value) {
            return preg_match("/^[A-Za-z0-9-_]+$/D", $value);
        }, "Spaltennamen dürfen nur alphanumerische Zeichen, Bindestriche und Unterstriche enthalten.", true);
        $field->validationClosures[] = new ValidationClosure(function ($value) {
            $this->internalIdColumnName = $value; // For check in ValidationClosure for comments column
            return Column::isNameAvailableInProject($this->project->id, $value);
        }, "Dieser Spaltenname wird in diesem Projekt bereits verwendet");
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
            return ! in_array(strtolower($value), Column::RESERVED_NAMES);
        }, "Der eingegebene Name ist reserviert", true);
        $field->validationClosures[] = new ValidationClosure(static function ($value) {
            return preg_match("/^[A-Za-z0-9-_]+$/D", $value);
        }, "Spaltennamen dürfen nur alphanumerische Zeichen, Bindestriche und Unterstriche enthalten.", true);
        $field->validationClosures[] = new ValidationClosure(function ($value) {
            return Column::isNameAvailableInProject($this->project->id, $value) && $this->internalIdColumnName !== $value;
        }, "Dieser Spaltenname wird in diesem Projekt bereits verwendet");
        $this->addField($field);

        $field = new SelectField("commentsFormat", "Ausgabeformat");
        $field->dependsOn = "comments";
        $field->dependsOnValue = CheckboxField::VALUE;
        $field->addOption(ExportProcess::COMMENTS_FORMAT_TEXT, "Einfaches Textformat");
        $field->addOption(ExportProcess::COMMENTS_FORMAT_JSON, "JSON (maschinenlesbar)");
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

        $field = new CheckboxField("generateSchemeDocs", "Dokumentation zur Datenstruktur generieren");
        $field->description = "Den Exportdateien wird eine automatisch generierte HTML-Datei mit Strukturinformationen beigefügt";
        $field->defaultValue = true;
        $this->addField($field);

        $field = new TextField("note", "Bemerkung");
        $field->required = false;
        $field->maxLength = Export::MAX_LENGTH_NOTE;
        $this->addField($field);

        $this->buttonLabel = "Exportieren";
    }

    /**
     * @throws Exception If an error occurs during exporting
     */
    public function perform(array $data)
    {
        $process = new ExportProcess($this->project);
        $process->includeInternalIds = $data["internalId"];

        if ($process->includeInternalIds) {
            $process->internalIdColumnName = $data["internalIdColumnName"];
        }

        $process->includeComments = $data["comments"];

        if ($process->includeComments) {
            $process->commentsColumnName = $data["commentsColumnName"];
            $process->commentsFormat = $data["commentsFormat"];
            $process->commentsAnonymize = $data["commentsAnonymize"];
            $process->commentsExcludeAPI = $data["commentsExcludeAPI"];
        }

        $process->generateSchemeDocs = $data["generateSchemeDocs"];
        $process->note = $data["note"];

        $this->export = $process->run();
    }
}
