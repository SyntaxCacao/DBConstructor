<?php

declare(strict_types=1);

namespace DBConstructor\Models;

use DBConstructor\Forms\Fields\Field;
use DBConstructor\SQL\MySQLConnection;

class RelationalColumn extends Column
{
    public static function create(string $tableId, string $targetTableId, /*string $labelColumnId, */ string $name, string $label, string $description = null, string $position, bool $nullable): string
    {
        MySQLConnection::$instance->execute("UPDATE `dbc_column_relational` SET `position`=`position`+1 WHERE `table_id`=? AND `position`>=?", [$tableId, $position]);

        MySQLConnection::$instance->execute("INSERT INTO `dbc_column_relational` (`table_id`, `target_table_id`, /*`label_column_id`, */`name`, `label`, `description`, `position`, `nullable`) VALUES (?, ?, /*?, */?, ?, ?, ?, ?)", [$tableId, $targetTableId, /*$labelColumnId, */ $name, $label, $description, $position, intval($nullable)]);

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

    /** @var string */
    public $targetTableId;

    /** @var string */
    public $targetTableName;

    /** @var string */
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
        $this->targetTableId = $data["target_table_id"];
        $this->targetTableName = $data["target_table_name"];
        $this->targetTableLabel = $data["target_table_label"];
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

    public function edit(string $name, string $label, string $description = null, bool $nullable)
    {
        MySQLConnection::$instance->execute("UPDATE `dbc_column_relational` SET `name`=?, `label`=?, `description`=?, `nullable`=? WHERE `id`=?", [$name, $label, $description, intval($nullable), $this->id]);
        $this->name = $name;
        $this->label = $label;
        $this->description = $description;
        $this->nullable = $nullable;
    }

    public function generateInput(Field $field, bool $edit = false)
    {
        // TODO Do actual validation
        // See workaround in validation.js
        $validationIndicator = "";

        if (! $this->nullable) {
            $validationIndicator .= '<div class="validation-step"><div class="validation-step-icon"><span class="bi bi-x-lg"></span></div><p class="validation-step-description">Enthält einen Wert</p></div>';
        }

        $validationIndicator .= '<div class="validation-step"><div class="validation-step-icon"><span class="bi bi-dash-lg"></span></div><p class="validation-step-description">Referenzierter Datensatz existiert</p></div>';
        $validationIndicator .= '<div class="validation-step"><div class="validation-step-icon"><span class="bi bi-dash-lg"></span></div><p class="validation-step-description">Referenzierter Datensatz ist gültig</p></div>';

        $this->generateInput_internal($field, $edit, $this->nullable, $validationIndicator, false, "Auswahl", 'data-nullable="'.var_export($this->nullable, true).'"');
    }

    public function move(int $newPosition)
    {
        parent::move_internal("dbc_column_relational", $newPosition);
    }
}
