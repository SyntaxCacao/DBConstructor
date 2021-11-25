<?php

declare(strict_types=1);

namespace DBConstructor\Models;

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
    public $description;

    /** @var string */
    public $position;

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
        $this->description = $data["description"];
        $this->position = $data["position"];
        $this->created = $data["created"];
    }

    public abstract function generateInput(Field $field, bool $edit = false);

    protected function generateInput_internal(Field $field, bool $edit, bool $valid, string $validationIndicator, bool $isTextual, string $insertLabel, string $labelData)
    {
        echo '<h2 class="main-subheading">'.htmlentities($this->label).'</h2>';
        echo '<div class="row page-table-insert-row break-md">';

        // field
        echo '<label class="column width-'.($edit ? '7' : '4').' '.($isTextual ? 'js-validate-within' : 'js-validate-relational');

        if ($edit && ! $valid) {
            echo ' page-table-insert-invalid';
        }

        echo ' form-block" data-rules-element="#validation-steps-'.($isTextual ? 'textual' : 'relational').'-'.htmlentities($this->id).'" '.$labelData.'>';
        echo '<p class="page-table-insert-label">'.$insertLabel.'</p>';
        echo $field->generateField();
        echo '</label>';

        // rules
        echo '<div class="column width-'.($edit ? '5' : '3').'">';
        echo '<p class="page-table-insert-label">Regeln</p>';
        echo '<div class="validation-steps" id="validation-steps-'.($isTextual ? "textual" : "relational").'-'.htmlentities($this->id).'">';
        echo $validationIndicator;
        echo '</div></div>';

        // description
        if (! $edit) {
            echo '<div class="column width-5 page-table-insert-description"><p class="page-table-insert-label">Erläuterung</p>';

            if (is_null($this->description)) {
                echo '<div class="markdown"><p><em>Keine Erläuterung vorhanden</em></p></div>';
            } else {
                echo '<div class="markdown">'.(new MarkdownParser)->parse($this->description).'</div>';
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
