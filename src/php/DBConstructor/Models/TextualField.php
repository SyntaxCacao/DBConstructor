<?php

declare(strict_types=1);

namespace DBConstructor\Models;

use DBConstructor\SQL\MySQLConnection;

class TextualField
{
    public static function createAll(string $rowId, array $fields)
    {
        MySQLConnection::$instance->prepare("INSERT INTO `dbc_field_textual` (`row_id`, `column_id`, `value`, `valid`) VALUES (?, ?, ?, ?)");

        foreach ($fields as $field) {
            MySQLConnection::$instance->executePrepared([$rowId, $field["column_id"], $field["value"], intval($field["valid"])]);
        }
    }

    public static function delete(string $columnId)
    {
        MySQLConnection::$instance->execute("DELETE FROM `dbc_field_textual` WHERE `column_id`=?", [$columnId]);
    }

    public static function loadRow(string $rowId): array
    {
        MySQLConnection::$instance->execute("SELECT f.*, c.`name` AS `column_name` FROM `dbc_field_textual` f LEFT JOIN `dbc_column_textual` c ON f.`column_id`=c.`id` WHERE `row_id`=?", [$rowId]);
        $result = MySQLConnection::$instance->getSelectedRows();
        $row = [];

        foreach ($result as $resultRow) {
            $field = new TextualField($resultRow);
            $row[$field->columnId]["obj"] = $field;
            $row[$field->columnId]["name"] = $resultRow["column_name"];
        }

        return $row;
    }

    /**
     * @return TextualField[][]
     */
    public static function loadTable(string $tableId): array
    {
        MySQLConnection::$instance->execute("SELECT f.* FROM `dbc_field_textual` f LEFT JOIN `dbc_row` r ON f.`row_id` = r.`id` WHERE r.`table_id`=?", [$tableId]);
        $result = MySQLConnection::$instance->getSelectedRows();
        $table = [];

        foreach ($result as $row) {
            $field = new TextualField($row);
            $table[$field->rowId][$field->columnId] = $field;
        }

        return $table;
    }

    /** @var string */
    public $id;

    /** @var string */
    public $rowId;

    /** @var string */
    public $columnId;

    /** @var string|null */
    public $value;

    /** @var bool|null */
    public $valid;

    /**
     * @param string[] $data
     */
    public function __construct(array $data)
    {
        $this->id = $data["id"];
        $this->rowId = $data["row_id"];
        $this->columnId = $data["column_id"];
        $this->value = $data["value"];

        if ($data["valid"] !== null) {
            $this->valid = $data["valid"] == "1";
        }
    }
}
