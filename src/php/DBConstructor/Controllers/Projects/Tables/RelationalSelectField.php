<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\Projects\Tables;

use DBConstructor\Application;
use DBConstructor\Controllers\Projects\ProjectsController;
use DBConstructor\Forms\Fields\GroupableField;
use DBConstructor\Models\RelationalField;
use DBConstructor\Models\Row;
use Exception;

class RelationalSelectField extends GroupableField
{
    public static $nextId = 1;

    /**
     * Used for recursion test during form validation.
     *
     * @var string|null
     */
    public $baseRowId;

    /** @var bool */
    public $nullable;

    /** @var Row|null */
    public $selection;

    /** @var string */
    public $targetTableId;

    public function __construct(string $name, string $label = null, bool $nullable, string $targetTableId, string $baseRowId = null)
    {
        parent::__construct($name, $label);
        $this->required = false;

        $this->baseRowId = $baseRowId;
        $this->nullable = $nullable;
        $this->targetTableId = $targetTableId;
    }

    public function generateField(): string
    {
        $html = '<input id="page-table-selector-'.self::$nextId.'-input" name="field-'.htmlentities($this->name).'" type="hidden"';

        if ($this->value !== null) {
            $html .= ' value="'.$this->value.'"';
        }

        $html .= ' data-nullable="'.intval($this->nullable).'" data-value-exists="'.intval($this->selection !== null).'" data-value-valid="'.intval($this->selection !== null && $this->selection->valid).'" data-value-deleted="'.intval($this->selection !== null && $this->selection->deleted).'">';
        $html .= '<div class="form-input page-table-selector">';

        if ($this->value === null || $this->selection === null) {
            $html .= '<span class="page-table-selector-value">';

            if ($this->value === null) {
                $html .= 'Keine Auswahl';
            } else {
                $html .= '#'.htmlentities($this->value);
            }

            $html .= '</span>';
            $html .= '<span class="validation-step-icon page-table-selector-indicator hide"><span class="bi bi-check"></span></span>';
        } else {
            $html .= '<span class="page-table-selector-value"><a class="main-link" href="'.Application::$instance->config["baseurl"].'/projects/'.ProjectsController::$projectId.'/tables/'.$this->selection->tableId.'/view/'.$this->selection->id.'/" target="_blank">#'.$this->selection->id.'</a></span>';
            $html .= '<span class="validation-step-icon page-table-selector-indicator">';

            if ($this->selection->deleted) {
                $html .= '<span class="bi bi-trash3"></span>';
            } else if ($this->selection->valid) {
                $html .= '<span class="bi bi-check"></span>';
            } else {
                $html .= '<span class="bi bi-x"></span>';
            }

            $html .= '</span>';
        }

        $html .= '<button class="button button-smallest page-table-selector-button js-open-modal" type="button" data-modal="modal-selector-'.self::$nextId.'"><span class="bi bi-pencil no-margin"></span></button>';
        $html .= '</div>';

        // selection modal
        $modal = '<div class="modal page-table-selector-modal" id="modal-selector-'.self::$nextId.'" data-selector-id="'.self::$nextId.'" data-project-id="'.ProjectsController::$projectId.'" data-table-id="'.$this->targetTableId.'">';
        $modal .= '<div class="modal-container">';
        $modal .= '<div class="modal-dialog">';
        $modal .= '<header class="modal-header">';
        $modal .= '<h3>Datensatz auswählen</h3>';
        $modal .= '<a class="modal-x js-close-modal" href="#"><span class="bi bi-x"></span></a>';
        $modal .= '</header>';
        $modal .= '<div class="modal-content"></div>';
        $modal .= '<div class="modal-actions">';
        $modal .= '<a class="button modal-action js-table-selector js-close-modal" href="#" data-row-id="">Keine Auswahl</a>';
        $modal .= '<a class="button button-disabled modal-action modal-action-right page-table-selector-modal-prev" href="#"><span class="bi bi-arrow-left no-margin"></span>&nbsp;&nbsp;Zurück</a>';
        $modal .= '<a class="button button-disabled modal-action page-table-selector-modal-next" href="#">Weiter&nbsp;&nbsp;<span class="bi bi-arrow-right no-margin"></span></a>';
        $modal .= '</div>';
        $modal .= '</div>';
        $modal .= '</div>';
        $modal .= '</div>';
        Application::$instance->modals[] = $modal;

        self::$nextId += 1;
        return $html;
    }

    public function hasValue(): bool
    {
        return true;
    }

    public function insertValue($value)
    {
        parent::insertValue($value);

        if ($this->value !== null) {
            $this->selection = Row::load($value);
        }
    }

    public function validate(): array
    {
        if (! ($this->value === null || $this->selection !== null)) {
            // In this case, there exists a value (= row ID), but no corresponding Row was found
            // Instead of showing an error message, value will be treated as if no Row was chosen
            $this->value = null;
        }

        if ($this->required && $this->value === null) {
            // Normally, this would not be necessary, as Form handles this automatically.
            // But in this class, hasValue() returns always true (for some reason?), so that the default mechanism does not work.
            // Also, this allows for a custom message.
            return ["Bitte wählen Sie einen Datensatz."];
        }

        if ($this->value !== null && $this->baseRowId !== null) {
            try {
                RelationalField::testRecursion($this->baseRowId, $this->value);
            } catch (Exception $exception) {
                return ["Der gewählte Datensatz referenziert diesen Datensatz unmittelbar oder mittelbar, dies wird (zur Zeit) nicht unterstützt."];
            }
        }

        $issues = [];
        $this->callClosures($issues);

        return $issues;
    }
}
