<?php

declare(strict_types=1);

namespace DBConstructor\Models;

use DBConstructor\SQL\MySQLConnection;
use DBConstructor\Util\JsonException;
use DBConstructor\Validation\Types\Type;
use PDOStatement;
use Throwable;

class TextualField
{
    public static function createAll(string $rowId, array $fields)
    {
        MySQLConnection::$instance->prepare("INSERT INTO `dbc_field_textual` (`row_id`, `column_id`, `value`, `valid`) VALUES (?, ?, ?, ?)");

        foreach ($fields as $field) {
            MySQLConnection::$instance->executePrepared([$rowId, $field["column_id"], $field["value"], intval($field["valid"])]);
        }
    }

    public static function deleteColumn(string $columnId)
    {
        MySQLConnection::$instance->execute("DELETE FROM `dbc_field_textual` WHERE `column_id`=?", [$columnId]);
    }

    public static function deleteRow(string $rowId)
    {
        MySQLConnection::$instance->execute("DELETE FROM `dbc_field_textual` WHERE `row_id`=?", [$rowId]);
    }

    public static function fill(string $tableId, string $columnId, string $fillValue = null, Type $validationType)
    {
        $validator = $validationType->buildValidator();
        $valid = $validator->validate($fillValue);

        MySQLConnection::$instance->execute("INSERT INTO `dbc_field_textual` (`row_id`, `column_id`, `value`, `valid`) SELECT `id`, ?, ?, ? FROM `dbc_row` WHERE `table_id`=?", [$columnId, $fillValue, intval($valid), $tableId]);

        if (! $valid) {
            Row::revalidateAllValid($tableId);
        }
    }

    /**
     * @return array<string, TextualField>
     */
    public static function loadColumn(string $columnId): array
    {
        MySQLConnection::$instance->execute("SELECT * FROM `dbc_field_textual` WHERE `column_id`=?", [$columnId]);
        $result = MySQLConnection::$instance->getSelectedRows();
        $fields = [];

        foreach ($result as $row) {
            $field = new TextualField($row);
            $fields[$field->rowId] = $field;
        }

        return $fields;
    }

    /**
     * @return array<string, TextualField>
     */
    public static function loadRow(string $rowId): array
    {
        MySQLConnection::$instance->execute("SELECT f.*, c.`name` AS `column_name` FROM `dbc_field_textual` f LEFT JOIN `dbc_column_textual` c ON f.`column_id`=c.`id` WHERE f.`row_id`=?", [$rowId]);
        $result = MySQLConnection::$instance->getSelectedRows();
        $row = [];

        foreach ($result as $resultRow) {
            $field = new TextualField($resultRow);
            $row[$field->columnId] = $field;
        }

        return $row;
    }

    /**
     * @param array<Row> $rows
     * @return array<string, array<string, TextualField>>
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

        MySQLConnection::$instance->execute("SELECT * FROM `dbc_field_textual` WHERE `row_id` IN (".$in.")");
        $result = MySQLConnection::$instance->getSelectedRows();
        $table = [];

        foreach ($result as $row) {
            $field = new TextualField($row);
            $table[$field->rowId][$field->columnId] = $field;
        }

        return $table;
    }

    public static function migrateSelectOptions(string $columnId, bool $toJson)
    {
        MySQLConnection::$instance->execute("SELECT * FROM `dbc_field_textual` WHERE `column_id`=? AND `value` IS NOT NULL", [$columnId]);
        $result = MySQLConnection::$instance->getSelectedRows();

        foreach ($result as $row) {
            $field = new TextualField($row);

            try {
                $options = json_decode($field->value);

                if ($toJson) {
                    if ($options !== null) {
                        if (count($options) === 0) {
                            MySQLConnection::$instance->execute("UPDATE `dbc_field_textual` SET `value`=NULL WHERE `id`=?", [$field->id]);
                            continue;
                        }

                        $valid = true;

                        foreach ($options as $value) {
                            if (! is_string($value)) {
                                $valid = false;
                                break;
                            }
                        }

                        if ($valid) {
                            $json = json_encode(array_values($options));

                            if ($json === false) {
                                throw new JsonException();
                            }

                            MySQLConnection::$instance->execute("UPDATE `dbc_field_textual` SET `value`=? WHERE `id`=?", [$json, $field->id]);
                            continue;
                        }
                    }

                    $json = json_encode([$field->value]);

                    if ($json === false) {
                        throw new JsonException();
                    }

                    MySQLConnection::$instance->execute("UPDATE `dbc_field_textual` SET `value`=? WHERE `id`=?", [$json, $field->id]);
                } else {
                    if ($options === null) {
                        throw new JsonException();
                    }

                    if (count($options) === 0) {
                        MySQLConnection::$instance->execute("UPDATE `dbc_field_textual` SET `value`=NULL WHERE `id`=?", [$field->id]);
                    } else if (count($options) === 1) {
                        MySQLConnection::$instance->execute("UPDATE `dbc_field_textual` SET `value`=? WHERE `id`=?", [$options[0], $field->id]);
                    } // doing nothing if there are multiple options selected
                }
            } catch (Throwable $throwable) {
                error_log("Error while migrating field #$field->id (record #$field->rowId): ".get_class($throwable)." in ".$throwable->getFile()." on line ".$throwable->getLine().": ".$throwable->getMessage());
            }
        }
    }

    public static function selectTableExport(string $tableId): PDOStatement
    {
        return MySQLConnection::$instance->executeSeparately("SELECT f.* FROM `dbc_field_textual` f LEFT JOIN `dbc_row` r ON f.`row_id` = r.`id` WHERE r.`table_id`=? AND r.`exportid` IS NOT NULL ORDER BY r.`exportid`", [$tableId]);
    }

    /** @var string */
    public $id;

    /** @var string */
    public $rowId;

    /** @var string */
    public $columnId;

    /**
     * TODO: Check if actually used somewhere, remove from loadRow() if not
     *
     * @var string|null
     */
    public $columnName;

    /** @var string|null */
    public $value;

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
        $this->value = $data["value"];

        if (isset($data["column_name"])) {
            $this->columnName = $data["column_name"];
        }

        if ($data["valid"] !== null) {
            $this->valid = $data["valid"] == "1";
        }
    }

    public function edit(string $userId, bool $api, Row $row, string $value = null, bool $valid)
    {
        MySQLConnection::$instance->execute("UPDATE `dbc_field_textual` SET `value`=?, `valid`=? WHERE `id`=?", [$value, intval($valid), $this->id]);
        Row::revalidate($row->id);
        $row->updateValidity();

        $row->setUpdated($userId);
        RowAction::logChange($row->id, $userId, $api, false, $this->columnId, $this->value, $value);

        // $this->value must not be updated earlier as old value is needed for logChange()
        $this->value = $value;
        $this->valid = $valid;
    }

    public function setValid(bool $valid, bool $revalidateRow = true)
    {
        MySQLConnection::$instance->execute("UPDATE `dbc_field_textual` SET `valid`=? WHERE `id`=?", [intval($valid), $this->id]);
        $this->valid = $valid;

        if ($revalidateRow) {
            Row::revalidate($this->rowId);
        }
    }
}
