<?php

declare(strict_types=1);

namespace DBConstructor\Models;

use DBConstructor\Controllers\Projects\Tables\RelationalSelectField;
use DBConstructor\Forms\Fields\Field;
use DBConstructor\SQL\MySQLConnection;

class RelationalColumn extends Column
{
    public static function create(string $tableId, string $targetTableId, /*string $labelColumnId, */ string $name, string $label, string $instructions = null, string $position, bool $nullable, bool $hide): string
    {
        MySQLConnection::$instance->execute("UPDATE `dbc_column_relational` SET `position`=`position`+1 WHERE `table_id`=? AND `position`>=?", [$tableId, $position]);

        MySQLConnection::$instance->execute("INSERT INTO `dbc_column_relational` (`table_id`, `target_table_id`, /*`label_column_id`, */`name`, `label`, `instructions`, `position`, `nullable`, `hide`) VALUES (?, ?, /*?, */?, ?, ?, ?, ?, ?)", [$tableId, $targetTableId, /*$labelColumnId, */ $name, $label, $instructions, $position, intval($nullable), intval($hide)]);

        return MySQLConnection::$instance->getLastInsertId();
    }

    /**
     * @return RelationalColumn|null
     */
    public static function load(string $id)
    {
        MySQLConnection::$instance->execute("SELECT c.*, t.`name` AS `target_table_name`, t.`label` AS `target_table_label` FROM `dbc_column_relational` c LEFT JOIN `dbc_table` t ON c.`target_table_id` = t.`id` WHERE c.`id`=?", [$id]);
        $result = MySQLConnection::$instance->getSelectedRows();

        if (count($result) != 1) {
            return null;
        }

        return new RelationalColumn($result[0]);
    }

    /**
     * @return array<string, RelationalColumn>
     */
    public static function loadList(string $tableId): array
    {
        MySQLConnection::$instance->execute("SELECT c.*, t.`name` AS `target_table_name`, t.`label` AS `target_table_label` FROM `dbc_column_relational` c LEFT JOIN `dbc_table` t ON c.`target_table_id` = t.`id` WHERE c.`table_id`=? ORDER BY c.`position`", [$tableId]);
        $result = MySQLConnection::$instance->getSelectedRows();
        $list = [];

        foreach ($result as $row) {
            $obj = new RelationalColumn($row);
            $list[$obj->id] = $obj;
        }

        return $list;
    }

    public static function loadReferencingColumns(string $tableId, bool $manualOrder): array
    {
        $sql = "SELECT c.*, t.`name` AS `table_name`, t.`label` AS `table_label` FROM `dbc_column_relational` c LEFT JOIN `dbc_table` t ON c.`table_id` = t.`id` WHERE c.`target_table_id`=? ";

        if ($manualOrder) {
            $sql .= "ORDER BY t.`position`";
        } else {
            $sql .= "ORDER BY t.`label`";
        }

        $sql .= ", c.`position`";

        MySQLConnection::$instance->execute($sql, [$tableId]);
        $result = MySQLConnection::$instance->getSelectedRows();
        $list = [];

        foreach ($result as $row) {
            $obj = new RelationalColumn($row);
            $list[$obj->id] = $obj;
        }

        return $list;
    }

    /** @var string|null */
    public $tableName;

    /** @var string|null */
    public $tableLabel;

    /** @var string */
    public $targetTableId;

    /** @var string|null */
    public $targetTableName;

    /** @var string|null */
    public $targetTableLabel;

    /** @var string */
    public $labelColumnId;

    /** @var bool */
    public $nullable;

    /**
     * @param array<string, string> $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
        $this->tableName = $data["table_name"] ?? null;
        $this->tableLabel = $data["table_label"] ?? null;
        $this->targetTableId = $data["target_table_id"];
        $this->targetTableName = $data["target_table_name"] ?? null;
        $this->targetTableLabel = $data["target_table_label"] ?? null;
        $this->labelColumnId = $data["label_column_id"];
        $this->nullable = $data["nullable"] == "1";
    }

    public function delete()
    {
        RelationalField::deleteColumn($this->id);
        MySQLConnection::$instance->execute("DELETE FROM `dbc_column_relational` WHERE `id`=?", [$this->id]);
        MySQLConnection::$instance->execute("UPDATE `dbc_column_relational` SET `position`=`position`-1 WHERE `table_id`=? AND `position`>=?", [$this->tableId, $this->position]);
        Row::revalidateAllInvalid($this->tableId);
    }

    public function edit(string $name, string $label, string $instructions = null, bool $nullable, bool $hide)
    {
        MySQLConnection::$instance->execute("UPDATE `dbc_column_relational` SET `name`=?, `label`=?, `instructions`=?, `nullable`=?, `hide`=? WHERE `id`=?", [$name, $label, $instructions, intval($nullable), intval($hide), $this->id]);
        $this->name = $name;
        $this->label = $label;
        $this->instructions = $instructions;
        $this->nullable = $nullable;
        $this->hide = $hide;
    }

    public function generateInput(Field $field, array $errorMessages, bool $edit = false)
    {
        /** @var RelationalSelectField $field */
        $validationIndicator = "";

        if (! $this->nullable) {
            if ($field->value === null) {
                $validationIndicator .= '<div class="validation-step"><div class="validation-step-icon"><span class="bi bi-x-lg"></span></div><p class="validation-step-description">Enthält einen Wert</p></div>';
            } else {
                $validationIndicator .= '<div class="validation-step"><div class="validation-step-icon"><span class="bi bi-check-lg"></span></div><p class="validation-step-description">Enthält einen Wert</p></div>';
            }
        }

        if ($field->selection === null) {
            if ($field->value === null) {
                $validationIndicator .= '<div class="validation-step"><div class="validation-step-icon"><span class="bi bi-dash-lg"></span></div><p class="validation-step-description">Referenzierter Datensatz existiert</p></div>';
            } else {
                $validationIndicator .= '<div class="validation-step"><div class="validation-step-icon"><span class="bi bi-x-lg"></span></div><p class="validation-step-description">Referenzierter Datensatz existiert</p></div>';
            }

            $validationIndicator .= '<div class="validation-step"><div class="validation-step-icon"><span class="bi bi-dash-lg"></span></div><p class="validation-step-description">Referenzierter Datensatz ist gültig</p></div>';
        } else {
            $validationIndicator .= '<div class="validation-step"><div class="validation-step-icon"><span class="bi bi-check-lg"></span></div><p class="validation-step-description">Referenzierter Datensatz existiert</p></div>';

            if ($field->selection->valid && ! $field->selection->deleted) {
                $validationIndicator .= '<div class="validation-step"><div class="validation-step-icon"><span class="bi bi-check-lg"></span></div><p class="validation-step-description">Referenzierter Datensatz ist gültig</p></div>';
            } else {
                $validationIndicator .= '<div class="validation-step"><div class="validation-step-icon"><span class="bi bi-x-lg"></span></div><p class="validation-step-description">Referenzierter Datensatz ist gültig</p></div>';
            }
        }

        // valid value doesn't matter and therefore can always be true
        $this->generateInput_internal($field, $errorMessages, $edit, true, $validationIndicator, false, "Auswahl");
    }

    public function move(int $newPosition)
    {
        parent::move_internal("dbc_column_relational", $newPosition);
    }
}
