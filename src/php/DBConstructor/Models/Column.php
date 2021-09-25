<?php

declare(strict_types=1);

namespace DBConstructor\Models;

use DBConstructor\SQL\MySQLConnection;

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

    /** @var string|null */
    public $rules;

    /** @var string */
    public $created;

    public function __construct(array $data)
    {
        $this->id = $data["id"];
        $this->tableId = $data["table_id"];
        $this->name = $data["name"];
        $this->label = $data["label"];
        $this->description = $data["description"];
        $this->position = $data["position"];
        $this->rules = $data["rules"];
        $this->created = $data["created"];
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
