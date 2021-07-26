<?php

declare(strict_types=1);

namespace DBConstructor\Models;

use DBConstructor\SQL\MySQLConnection;

class RelationalField
{
    const VALIDITY_INVALID = "invalid";

    const VALIDITY_UNCHECKED = "unchecked";

    const VALIDITY_VALID = "valid";

    public static function createAll(string $rowId, array $fields)
    {
        MySQLConnection::$instance->prepare("INSERT INTO `dbc_field_relational` (`row_id`, `column_id`, `target_row_id`, `validity`) VALUES (?, ?, ?, ?)");

        foreach ($fields as $field) {
            MySQLConnection::$instance->executePrepared([$rowId, $field["column_id"], $field["target_row_id"], $field["validity"]]);
        }
    }

    /**
     * @return Table[]
     */
    public static function loadTable(string $tableId): array
    {
        MySQLConnection::$instance->execute("SELECT f.*, tr.`exportid` AS `target_row_exportid` FROM `dbc_field_relational` f LEFT JOIN `dbc_row` r ON f.`row_id` = r.`id` LEFT JOIN `dbc_row` tr ON f.`target_row_id` = tr.`id` WHERE r.`table_id`=?", [$tableId]);
        $result = MySQLConnection::$instance->getSelectedRows();
        $table = [];

        foreach ($result as $row) {
            $field = new RelationalField($row);
            $table[$field->rowId][$field->columnId]["obj"] = $field;
            // TODO: Anders machen!!!!
            $table[$field->rowId][$field->columnId]["target"] = TextualField::loadRow($field->targetRowId);
        }

        return $table;
    }

    /**
     * @var string
     */
    public $id;

    /**
     * @var string
     */
    public $rowId;

    /**
     * @var string
     */
    public $columnId;

    /**
     * @var string
     */
    public $targetRowId;

    /**
     * @var string
     */
    public $targetRowExportId;

    /**
     * @var string
     */
    public $validity;

    /**
     * @param string[] $data
     */
    public function __construct(array $data)
    {
        $this->id = $data["id"];
        $this->rowId = $data["row_id"];
        $this->columnId = $data["column_id"];
        $this->targetRowId = $data["target_row_id"];
        $this->targetRowExportId = $data["target_row_exportid"];
        $this->validity = $data["validity"];
    }
}
