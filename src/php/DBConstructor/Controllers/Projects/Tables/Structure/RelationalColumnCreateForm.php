<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\Projects\Tables\Structure;

use DBConstructor\Application;
use DBConstructor\Forms\Fields\CheckboxField;
use DBConstructor\Forms\Fields\ValidationClosure;
use DBConstructor\Forms\Form;
use DBConstructor\Forms\Fields\SelectField;
use DBConstructor\Forms\Fields\TextField;
use DBConstructor\Forms\Fields\TextareaField;
use DBConstructor\Models\RelationalColumn;
use DBConstructor\Models\Table;
use DBConstructor\Validation\Validator;

class RelationalColumnCreateForm extends Form
{
    public $projectId;
    public $tableId;

    public function __construct()
    {
        parent::__construct("relational-column-create-form");
    }

    public function init($projectId, $tableId, $tablePosition)
    {
        $this->projectId = $projectId;
        $this->tableId = $tableId;

        $field = new TextField("label", "Bezeichnung");
        $field->minLength = 3;
        $field->maxLength = 30;
        $this->addField($field);

        $field = new TextField("name", "Technischer Name");
        $field->maxLength = 30;
        $field->monospace = true;
        $field->validationClosures[] = new ValidationClosure(static function ($value) {
            return strtolower($value) != "id";
        }, 'Der Name "id" ist reserviert.');
        $field->validationClosures[] = new ValidationClosure(static function ($value) {
            return preg_match("/^[A-Za-z0-9-_]+$/", $value);
        }, "Spaltennamen dürfen nur alphanumerische Zeichen, Bindestriche und Unterstriche enthalten.", true);
        $field->validationClosures[] = new ValidationClosure(function ($value) {
            // TODO: For edit form, check if name equals current name
            return RelationalColumn::isNameAvailable($this->tableId, $value);
        }, "Die Tabelle enthält bereits eine Spalte mit diesem Namen.");
        $this->addField($field);

        $typeFieldName = "target-table";
        $field = new SelectField($typeFieldName, "Zieltabelle");

        $tables = Table::loadListAbove($this->projectId, $tablePosition);

        foreach ($tables as $table) {
            $field->addOption($table->id, $table->label ." (".$table->name.")");
        }

        $this->addField($field);

        $field = new CheckboxField("rule-null-allowed", "Angabe ist optional");
        $field->description = "Wenn kein Wert angegeben wird, wird null gespeichert.";
        $this->addField($field);

        $field = new TextareaField("description", "Erläuterung");
        $field->maxLength = 1000;
        $field->required = false;
        $this->addField($field);
    }

    public function perform(array $data)
    {
        $rules = Validator::createRelationValidator(! $data["rule-null-allowed"])->toJSON();

        RelationalColumn::create($this->tableId, $data["target-table"], $data["name"], $data["label"], $data["description"], $rules);

        Application::$instance->redirect("projects/$this->projectId/tables/$this->tableId/structure", "columncreated");
    }
}
