<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\Projects\Exports;

use DBConstructor\Application;
use DBConstructor\Controllers\Projects\ProjectsController;
use DBConstructor\Forms\Fields\TextField;
use DBConstructor\Forms\Form;
use DBConstructor\Models\Export;

class NoteEditForm extends Form
{
    /** @var Export */
    public $export;

    public function __construct()
    {
        parent::__construct("note-edit");
    }

    public function init(Export $export)
    {
        $this->export = $export;

        $field = new TextField("note", "Bemerkung");
        $field->required = false;
        $field->maxLength = Export::MAX_LENGTH_NOTE;
        $field->defaultValue = $export->note;
        $this->addField($field);
    }

    public function perform(array $data)
    {
        $this->export->setNote($data["note"]);
        Application::$instance->redirect("projects/".ProjectsController::$projectId."/exports/".$this->export->id);
    }
}
