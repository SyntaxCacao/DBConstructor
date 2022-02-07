<?php

declare(strict_types=1);

namespace DBConstructor\Models;

use DBConstructor\SQL\MySQLConnection;
use DBConstructor\Util\JsonException;

class RelationalField
{
    /**
     * 1st ?: intval(nullable)
     * 2nd-4th ?: targetRowId
     *
     * Validation process for RelationalField:
     *
     * (targetRow is nullable AND (targetRow is unset OR targetRow exists but was deleted))
     * OR
     * (targetRow exists, was not deleted, and is valid)
     */
    const VALIDATION_SUBQUERY = "(SELECT ((? AND (? IS NULL OR (SELECT `deleted` FROM `dbc_row` WHERE `id`=?) = 1)) OR ((SELECT (`valid` AND NOT `deleted`) FROM `dbc_row` WHERE `id`=?) <=> 1)))";

    /**
     * @param array<array<string>> $fields
     */
    public static function createAll(string $rowId, array $fields)
    {
        MySQLConnection::$instance->prepare("INSERT INTO `dbc_field_relational` (`row_id`, `column_id`, `target_row_id`, `valid`) VALUES (?, ?, ?, ".RelationalField::VALIDATION_SUBQUERY.")");

        foreach ($fields as $field) {
            MySQLConnection::$instance->executePrepared([$rowId, $field["column_id"], $field["target_row_id"], intval($field["column_nullable"]), $field["target_row_id"], $field["target_row_id"], $field["target_row_id"]]);
        }
    }

    public static function deleteColumn(string $columnId)
    {
        MySQLConnection::$instance->execute("DELETE FROM `dbc_field_relational` WHERE `column_id`=?", [$columnId]);
    }

    public static function deleteRow(string $rowId)
    {
        MySQLConnection::$instance->execute("DELETE FROM `dbc_field_relational` WHERE `row_id`=?", [$rowId]);
    }

    public static function fill(string $tableId, string $columnId, bool $nullable)
    {
        MySQLConnection::$instance->execute("INSERT INTO `dbc_field_relational` (`row_id`, `column_id`, `target_row_id`, `valid`) SELECT `id`, ?, null, ? FROM `dbc_row` WHERE `table_id`=?", [$columnId, intval($nullable), $tableId]);

        if (! $nullable) {
            Row::revalidateAllValid($tableId);
        }
    }

    /**
     * @return array<RelationalField>
     */
    public static function loadReferencingFields(string $rowId): array
    {
        //MySQLConnection::$instance->execute("SELECT f.*, c.`nullable` AS `column_nullable` FROM `dbc_field_relational` f LEFT JOIN `dbc_row` r ON f.`row_id`=r.`id` LEFT JOIN `dbc_column_relational` c ON f.`column_id`=c.`id` WHERE f.`target_row_id`=? AND r.`valid`=TRUE", [$rowId]);
        MySQLConnection::$instance->execute("SELECT f.*, c.`nullable` AS `column_nullable` FROM `dbc_field_relational` f LEFT JOIN `dbc_column_relational` c ON f.`column_id`=c.`id` WHERE f.`target_row_id`=?", [$rowId]);
        $result = MySQLConnection::$instance->getSelectedRows();
        $fields = [];

        foreach ($result as $row) {
            $fields[] = new RelationalField($row);
        }

        return $fields;
    }

    /**
     * @return array<string, RelationalField>
     */
    public static function loadRow(string $rowId): array
    {
        MySQLConnection::$instance->execute("SELECT f.*, (tr.`id` IS NOT NULL) AS `target_row_exists`, tr.`valid` AS `target_row_valid`, tr.`exportid` AS `target_row_exportid` FROM `dbc_field_relational` f LEFT JOIN `dbc_row` tr ON f.`target_row_id` = tr.`id` WHERE f.`row_id`=?", [$rowId]);
        $result = MySQLConnection::$instance->getSelectedRows();
        $row = [];

        foreach ($result as $resultRow) {
            $field = new RelationalField($resultRow);
            $row[$field->columnId] = $field;
        }

        return $row;
    }

    /**
     * @param array<Row> $rows
     * @return array<string, array<string, RelationalField>>
     */
    public static function loadRows(array &$rows): array
    {
        $in = "";
        $first = true;

        foreach ($rows as $row) {
            if ($first) {
                $first = false;
            } else {
                $in .= ", ";
            }

            $in .= $row->id;
        }

        MySQLConnection::$instance->execute("SELECT f.*, (tr.`id` IS NOT NULL) AS `target_row_exists`, tr.`valid` AS `target_row_valid`, tr.`exportid` AS `target_row_exportid` FROM `dbc_field_relational` f LEFT JOIN `dbc_row` tr ON f.`target_row_id` = tr.`id` WHERE f.`row_id` IN (".$in.")");
        $result = MySQLConnection::$instance->getSelectedRows();
        $table = [];

        foreach ($result as $row) {
            $field = new RelationalField($row);
            $table[$field->rowId][$field->columnId] = $field;
        }

        return $table;
    }

    /**
     * @return array<string, array<string, RelationalField>>
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

    /**
     * @throws JsonException
     */
    public static function nullifyReferencing(string $userId, Row $row)
    {
        $fields = RelationalField::loadReferencingFields($row->id);
        $rows = Row::loadReferencing($row->id);

        foreach ($fields as $field) {
            $field->edit($userId, $rows[$field->rowId], null, $field->columnNullable);
        }
    }

    public static function revalidateNullValues(string $columnId, bool $nullable)
    {
        MySQLConnection::$instance->execute("SELECT * FROM `dbc_field_relational` WHERE `column_id`=? AND `target_row_id` IS NULL", [$columnId]);
        $result = MySQLConnection::$instance->getSelectedRows();

        foreach ($result as $row) {
            (new RelationalField($row))->revalidate($nullable);
        }
    }

    public static function revalidateReferencing(string $rowId)
    {
        $fields = RelationalField::loadReferencingFields($rowId);

        foreach ($fields as $field) {
            $field->revalidate($field->columnNullable);
        }
    }

    /** @var string */
    public $id;

    /** @var string */
    public $rowId;

    /** @var string */
    public $columnId;

    /** @var bool|null */
    public $columnNullable;

    /** @var array<string, TextualField>|null */
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
     * @param array<string, string> $data
     */
    public function __construct(array $data)
    {
        $this->id = $data["id"];
        $this->rowId = $data["row_id"];
        $this->columnId = $data["column_id"];
        $this->targetRowId = $data["target_row_id"];

        if (isset($data["column_nullable"])) {
            $this->columnNullable = $data["column_nullable"] == "1";
        }

        if (isset($data["target_row_exists"])) {
            $this->targetRowExists = $data["target_row_exists"] == "1";
        }

        if (isset($data["target_row_exportid"])) {
            $this->targetRowExportId = $data["target_row_exportid"];
        }

        if (isset($data["target_row_valid"])) {
            $this->targetRowValid = $data["target_row_valid"] == "1";
        }

        if (isset($data["valid"])) {
            $this->valid = $data["valid"] == "1";
        }
    }

    /**
     * @throws JsonException
     */
    public function edit(string $userId, Row $row, string $targetRowId = null, bool $nullable)
    {
        MySQLConnection::$instance->execute("UPDATE `dbc_field_relational` SET `target_row_id`=? WHERE `id`=?", [$targetRowId, $this->id]);
        $prevValue = $this->targetRowId;
        $this->targetRowId = $targetRowId;

        $this->revalidate($nullable);
        $row->updateValidity();

        $row->setUpdated($userId);
        RowAction::logChange($row->id, $userId, true, $this->columnId, $prevValue, $targetRowId);
    }

    /**
     * @return array<string, TextualField>|null
     * @deprecated Don't use this to load targetRow for a larger number of RelationalFields
     *       as each execution requires a query to be run
     */
    public function getTargetRow()
    {
        if (! isset($this->targetRow) && $this->targetRowId !== null) {
            $this->targetRow = TextualField::loadRow($this->targetRowId);
        }

        return $this->targetRow;
    }

    public function revalidate(bool $nullable)
    {
        MySQLConnection::$instance->execute("UPDATE `dbc_field_relational` SET `valid`=".RelationalField::VALIDATION_SUBQUERY." WHERE `id`=?", [intval($nullable), $this->targetRowId, $this->targetRowId, $this->targetRowId, $this->id]);
        $this->updateValidity();
        Row::revalidate($this->rowId);
    }

    public function updateValidity()
    {
        MySQLConnection::$instance->execute("SELECT `valid` FROM `dbc_field_relational` WHERE `id`=?", [$this->id]);
        $this->valid = MySQLConnection::$instance->getSelectedRows()[0]["valid"] === "1";
    }
}
