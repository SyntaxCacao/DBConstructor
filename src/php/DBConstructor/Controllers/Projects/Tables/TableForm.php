<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\Projects\Tables;

use DBConstructor\Application;
use DBConstructor\Forms\Fields\MarkdownField;
use DBConstructor\Forms\Fields\ValidationClosure;
use DBConstructor\Forms\Form;
use DBConstructor\Forms\Fields\TextField;
use DBConstructor\Models\Table;

class TableForm extends Form
{
    /** @var string; */
    public $projectId;

    /** @var Table|null */
    public $table;

    public function __construct()
    {
        parent::__construct("table-create-form");
    }

    /**
     * @param Table|null $table null on creation
     */
    public function init(string $projectId, Table $table = null)
    {
        $this->projectId = $projectId;
        $this->table = $table;

        $field = new TextField("label", "Bezeichnung");
        $field->minLength = 3;
        $field->maxLength = 30;

        if (! is_null($table)) {
            $field->defaultValue = $table->label;
        }

        $this->addField($field);

        $field = new TextField("name", "Technischer Name");
        $field->maxLength = 30;
        $field->monospace = true;
        $field->validationClosures[] = new ValidationClosure(function ($value) {
            return preg_match("/^[A-Za-z0-9-_]+$/", $value);
        }, "Tabellennamen dürfen nur alphanumerische Zeichen, Bindestriche und Unterstriche enthalten.", true);

        if (is_null($table)) {
            $field->validationClosures[] = new ValidationClosure(function ($value) {
                return Table::isNameAvailable($value);
            }, "Dieser Tabellenname ist bereits vergeben.");
        } else {
            $field->validationClosures[] = new ValidationClosure(function ($value) {
                return $value == $this->table->name || Table::isNameAvailable($value);
            }, "Dieser Tabellenname ist bereits vergeben.");

            $field->defaultValue = $table->name;
        }

        $this->addField($field);

        $field = new MarkdownField("description", "Beschreibung");
        $field->larger = false;
        $field->maxLength = 1000;
        $field->required = false;

        if (! is_null($table)) {
            $field->defaultValue = $table->description;
        }

        $this->addField($field);
    }

    public function perform(array $data)
    {
        if (is_null($this->table)) {
            // create
            $id = Table::create($this->projectId, $data["name"], $data["label"], $data["description"]);
            Application::$instance->redirect("projects/$this->projectId/tables/$id");
        } else {
            // edit
            $this->table->edit($data["name"], $data["label"], $data["description"]);
        }
    }
}
