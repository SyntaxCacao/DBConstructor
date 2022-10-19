<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\Projects\Tables\View;

use DBConstructor\Application;
use DBConstructor\Controllers\Projects\ProjectsController;
use DBConstructor\Controllers\Projects\Tables\RelationalSelectField;
use DBConstructor\Forms\Fields\ValidationClosure;
use DBConstructor\Forms\Form;
use DBConstructor\Models\RelationalField;
use DBConstructor\Models\Row;
use DBConstructor\Models\RowAction;
use DBConstructor\Util\JsonException;
use Exception;

class RedirectForm extends Form
{
    /** @var string */
    public $originRowId;

    /** @var array<RelationalField> */
    public $references;

    /** @var string */
    public $tableId;

    public function __construct()
    {
        parent::__construct("row-redirect-form");
    }

    /**
     * @param array<RelationalField> $references
     */
    public function init(array $references, string $originRowId, string $tableId)
    {
        $this->references = $references;
        $this->originRowId = $originRowId;
        $this->tableId = $tableId;

        $field = new RelationalSelectField("target", "Zieldatensatz wählen", false, $tableId);
        $field->required = true;

        $field->validationClosures[] = new ValidationClosure(function ($value) {
            return $value !== $this->originRowId;
        }, "Es wurde der bisherige Datensatz gewählt.", true);

        $field->validationClosures[] = new ValidationClosure(function ($value) {
            try {
                foreach ($this->references as $field) {
                    RelationalField::testRecursion($value, $field->id);
                }

                return true;
            } catch (Exception $exception) {
                return false;
            }
        }, "Der gewählte Datensatz referenziert wenigstens einen der zu verändernden Datensätze unmittelbar oder mittelbar, dies wird (zur Zeit) nicht unterstützt.");

        $this->addField($field);

        $this->buttonLabel = "Umleiten";
    }

    /**
     * @throws JsonException
     */
    public function perform(array $data)
    {
        $row = Row::loadReferencing($this->originRowId);

        foreach ($this->references as $field) {
            $field->edit(Application::$instance->user->id, $row[$field->rowId], $data["target"], $field->columnNullable);
        }

        RowAction::logRedirection(Application::$instance->user->id, $this->originRowId, $data["target"], count($this->references));

        Application::$instance->redirect("projects/".ProjectsController::$projectId."/tables/".$this->tableId."/view/".$this->originRowId);
    }
}
