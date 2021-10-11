<?php

declare(strict_types=1);

namespace DBConstructor\Models;

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
     * @return RelationalColumn[]
     */
    public static function loadList(string $tableId): array
    {
        MySQLConnection::$instance->execute("SELECT c.*, t.`name` AS `target_table_name`, t.`label` AS `target_table_label` FROM `dbc_column_relational` c LEFT JOIN `dbc_table` t ON c.`target_table_id` = t.`id` WHERE c.`table_id`=? ORDER BY c.`position`", [$tableId]);
        $result = MySQLConnection::$instance->getSelectedRows();
        $list = [];

        foreach ($result as $row) {
            $list[] = new RelationalColumn($row);
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
        RelationalField::delete($this->id);
        MySQLConnection::$instance->execute("DELETE FROM `dbc_column_relational` WHERE `id`=?", [$this->id]);
        MySQLConnection::$instance->execute("UPDATE `dbc_column_relational` SET `position`=`position`-1 WHERE `table_id`=? AND `position`>=?", [$this->tableId, $this->position]);
    }

    public function edit(string $targetTableId, string $name, string $label, string $description = null, bool $nullable)
    {
        MySQLConnection::$instance->execute("UPDATE `dbc_column_relational` SET `target_table_id`=?, `name`=?, `label`=?, `description`=?, `nullable`=? WHERE `id`=?", [$targetTableId, $name, $label, $description, $nullable, $this->id]);
        $this->targetTableId = $targetTableId;
        $this->name = $name;
        $this->label = $label;
        $this->description = $description;
        $this->nullable = $nullable;
    }

    public function move(int $newPosition)
    {
        parent::move_internal("dbc_column_relational", $newPosition);
    }
}
