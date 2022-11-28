<?php

declare(strict_types=1);

namespace DBConstructor\Models;

use DBConstructor\Application;
use DBConstructor\Controllers\Projects\ProjectsController;
use DBConstructor\Forms\Fields\Field;
use DBConstructor\SQL\MySQLConnection;
use DBConstructor\Util\MarkdownParser;

abstract class Column
{
    public static function isNameAvailable(string $tableId, string $name): bool
    {
        MySQLConnection::$instance->execute("SELECT COUNT(*) AS `count` FROM `dbc_column_relational` WHERE `table_id`=? AND `name`=?", [$tableId, $name]);
        $result = MySQLConnection::$instance->getSelectedRows();

        if ($result[0]["count"] !== "0") {
            return false;
        }

        MySQLConnection::$instance->execute("SELECT COUNT(*) AS `count` FROM `dbc_column_textual` WHERE `table_id`=? AND `name`=?", [$tableId, $name]);
        $result = MySQLConnection::$instance->getSelectedRows();
        return $result[0]["count"] === "0";
    }

    /** @var string */
    public $id;

    /** @var string */
    public $tableId;

    /** @var string */
    public $name;

    /** @var string */
    public $label;

    /** @var string|null */
    public $instructions;

    /** @var string */
    public $position;

    /** @var bool */
    public $hide;

    /** @var string */
    public $created;

    /**
     * @param array<string, string> $data
     */
    public function __construct(array $data)
    {
        $this->id = $data["id"];
        $this->tableId = $data["table_id"];
        $this->name = $data["name"];
        $this->label = $data["label"];
        $this->instructions = $data["instructions"];
        $this->hide = $data["hide"] === "1";
        $this->position = $data["position"];
        $this->created = $data["created"];
    }

    public abstract function generateInput(Field $field, array $errorMessages, bool $edit = false);

    protected function generateInput_internal(Field $field, array $errorMessages, bool $edit, bool $valid, string $validationIndicator, bool $isTextual, string $insertLabel, string $labelData = null)
    {
        // header
        echo '<header class="main-subheader">';
        echo '<h2 class="main-subheading">'.htmlentities($this->label).'</h2>';

        if (ProjectsController::$isManager || ($edit && $this->instructions !== null)) {
            echo '<div class="main-header-actions">';

            // instructions button
            if ($edit && $this->instructions !== null) {
                $instructionsModalId = 'modal-instructions-'.($isTextual ? "txt" : "rel").'-'.$this->id;
                echo '<a class="button button-small main-header-action js-open-modal" href="#" tabindex="-1" title="Erläuterungen anzeigen" data-modal="'.$instructionsModalId.'">';
                echo '<span class="bi bi-book no-margin"></span>';
                echo '</a>';

                // instructions modal
                $modal = '<div class="modal" id="'.$instructionsModalId.'">';
                $modal .= '<div class="modal-container">';
                $modal .= '<div class="modal-dialog modal-dialog-lg">';
                $modal .= '<header class="modal-header">';
                $modal .= '<h3>Erläuterung</h3>';
                $modal .= '<a class="modal-x js-close-modal" href="#"><span class="bi bi-x-lg"></span></a>';
                $modal .= '</header>';
                $modal .= '<div class="modal-content markdown">';
                $modal .= MarkdownParser::parse($this->instructions);
                $modal .= '</div>';
                $modal .= '<div class="modal-actions">';
                $modal .= '<a class="button modal-action modal-action-right js-close-modal">Schließen</a>';
                $modal .= '</div>';
                $modal .= '</div>';
                $modal .= '</div>';
                $modal .= '</div>';

                Application::$instance->modals[] = $modal;
            }

            // edit button
            if (ProjectsController::$isManager) {
                echo '<a class="button button-small main-header-action" href="'.Application::$instance->config["baseurl"].'/projects/'.ProjectsController::$projectId.'/tables/'.$this->tableId.'/structure/'.($isTextual ? "textual" : "relational").'/'.$this->id.'/edit/?return='.urlencode($_SERVER["REQUEST_URI"]).'" tabindex="-1" title="Feld bearbeiten">';
                echo '<span class="bi bi-pencil no-margin"></span>';
                echo '</a>';
            }

            echo '</div>';
        }

        echo '</header>';

        echo '<div class="row page-table-insert-row break-md">';

        // field
        echo '<label class="column width-'.($edit ? '7' : '4').($isTextual ? ' js-validate-within' : '');

        if ($edit && $isTextual && ! $valid) {
            echo ' page-table-insert-invalid';
        }

        echo ' form-block"';

        if ($isTextual) {
            // used for normalization in validation.js
            /** @var TextualColumn $this */
            echo ' data-type="'.$this->type.'"';
            /** @var Column $this */
        }

        echo ' data-rules-element="#validation-steps-'.($isTextual ? 'textual' : 'relational').'-'.htmlentities($this->id).'"'.($labelData === null ? '' : ' '.$labelData).'>';
        echo '<p class="page-table-insert-label">'.$insertLabel.'</p>';
        echo $field->generateField();

        foreach ($errorMessages as $errorMessage) {
            echo '<p class="form-error">'.htmlentities($errorMessage).'</p>';
        }

        echo '</label>';

        // rules
        echo '<div class="column width-'.($edit ? '5' : '3').'">';
        echo '<p class="page-table-insert-label">Regeln</p>';
        echo '<div class="validation-steps" id="validation-steps-'.($isTextual ? "textual" : "relational").'-'.htmlentities($this->id).'">';
        echo $validationIndicator;
        echo '</div></div>';

        // instructions
        if (! $edit) {
            echo '<div class="column width-5 page-table-insert-instructions"><p class="page-table-insert-label">Erläuterung</p>';

            if (is_null($this->instructions)) {
                echo '<div class="markdown"><p><em>Keine Erläuterung vorhanden</em></p></div>';
            } else {
                echo '<div class="markdown">'.MarkdownParser::parse($this->instructions).'</div>';
            }

            echo '</div>';
        }

        echo '</div>';
    }

    protected function move_internal(string $tableName, int $newPosition)
    {
        $oldPosition = intval($this->position);

        if ($oldPosition > $newPosition) {
            // move down
            MySQLConnection::$instance->execute("UPDATE `".$tableName."` SET `position`=`position`+1 WHERE `table_id`=? AND `position`<? AND `position`>=?", [$this->tableId, $oldPosition, $newPosition]);
        } else {
            // move up
            MySQLConnection::$instance->execute("UPDATE `".$tableName."` SET `position`=`position`-1 WHERE `table_id`=? AND `position`>? AND `position`<=?", [$this->tableId, $oldPosition, $newPosition]);
        }

        MySQLConnection::$instance->execute("UPDATE `".$tableName."` SET `position`=? WHERE `id`=?", [$newPosition, $this->id]);
        $this->position = (string) $newPosition;
    }
}
