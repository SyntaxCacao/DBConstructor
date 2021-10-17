<?php

declare(strict_types=1);

namespace DBConstructor\Models;

use DBConstructor\SQL\MySQLConnection;

class RelationalField
{
    /**
     * @param string[][] $fields
     */
    public static function createAll(string $rowId, array $fields)
    {
        MySQLConnection::$instance->prepare("INSERT INTO `dbc_field_relational` (`row_id`, `column_id`, `target_row_id`, `valid`) VALUES (?, ?, ?, (SELECT ((? AND ? IS NULL) OR ((SELECT `valid` FROM `dbc_row` WHERE `id`=?) <=> 1))))");

        foreach ($fields as $field) {
            MySQLConnection::$instance->executePrepared([$rowId, $field["column_id"], $field["target_row_id"], intval($field["column_nullable"]), $field["target_row_id"], $field["target_row_id"]]);
        }
    }

    public static function delete(string $columnId)
    {
        MySQLConnection::$instance->execute("DELETE FROM `dbc_field_relational` WHERE `column_id`=?", [$columnId]);
    }

    /**
     * @return RelationalField[][]
     */
    public static function loadTable(string $tableId): array
    {
        MySQLConnection::$instance->execute("SELECT f.*, (tr.`id` IS NOT NULL) AS `target_row_exists`, tr.`valid` AS `target_row_valid`, tr.`exportid` AS `target_row_exportid` FROM `dbc_field_relational` f LEFT JOIN `dbc_row` r ON f.`row_id` = r.`id` LEFT JOIN `dbc_row` tr ON f.`target_row_id` = tr.`id` WHERE r.`table_id`=?", [$tableId]);
        $result = MySQLConnection::$instance->getSelectedRows();
        $table = [];

        foreach ($result as $row) {
            $field = new RelationalField($row);
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

    /** @var array|null */
    public $targetRow;

    /** @var bool */
    public $targetRowExists;

    /** @var string */
    public $targetRowId;

    /** @var bool|null */
    public $targetRowValid;

    /** @var string */
    public $targetRowExportId;

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
        $this->targetRowExists = $data["target_row_exists"] == "1";
        $this->targetRowId = $data["target_row_id"];
        $this->targetRowExportId = $data["target_row_exportid"];

        if ($data["target_row_valid"] !== null) {
            $this->targetRowValid = $data["target_row_valid"] == "1";
        }

        if ($data["valid"] !== null) {
            $this->valid = $data["valid"] == "1";
        }
    }

    /**
     * TODO: Don't use this to load targetRow for a larger number of RelationalFields
     *       as each execution requires a query to be run
     *
     * @return array|null
     */
    public function getTargetRow()
    {
        if (! isset($this->targetRow) && $this->targetRowId !== null) {
            $this->targetRow = TextualField::loadRow($this->targetRowId);
        }

        return $this->targetRow;
    }
}
