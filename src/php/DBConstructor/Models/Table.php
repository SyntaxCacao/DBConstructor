<?php

declare(strict_types=1);

namespace DBConstructor\Models;

use DBConstructor\SQL\MySQLConnection;

class Table
{
    public static function create(string $projectId, string $name, string $label, string $instructions = null): string
    {
        MySQLConnection::$instance->execute("SELECT `position` FROM `dbc_table` WHERE `project_id`=? ORDER BY `position` DESC LIMIT 1", [$projectId]);

        $result = MySQLConnection::$instance->getSelectedRows();
        $position = 1;

        if (count($result) > 0) {
            $position = intval($result[0]["position"]) + 1;
        }

        MySQLConnection::$instance->execute("INSERT INTO `dbc_table` (`project_id`, `name`, `label`, `instructions`, `position`) VALUES (?, ?, ?, ?, ?)", [$projectId, $name, $label, $instructions, $position]);

        return MySQLConnection::$instance->getLastInsertId();
    }

    public static function isNameAvailable(string $projectId, string $name): bool
    {
        MySQLConnection::$instance->execute("SELECT COUNT(*) AS `count` FROM `dbc_table` WHERE `project_id`=? AND `name`=?", [$projectId, $name]);
        $result = MySQLConnection::$instance->getSelectedRows();
        return $result[0]["count"] === "0";
    }

    /**
     * @return Table|null
     */
    public static function load(string $id)
    {
        MySQLConnection::$instance->execute("SELECT * FROM `dbc_table` WHERE `id`=?", [$id]);
        $result = MySQLConnection::$instance->getSelectedRows();

        if (count($result) != 1) {
            return null;
        }

        return new Table($result[0]);
    }

    /**
     * @param bool $manualOrder Tables will be ordered by (manually assigned) position if true, by names/labels if false.
     * @param bool $orderByLabel If $manualOrder is false, tables will be ordered by labels if true, by names if false.
     * @param bool $rowCounts If true, number of rows (not deleted; invalid but not deleted; flagged) for each table will be included.
     * @param string|null $assigneeId If not null, number of rows assigned to the given user will be included for each table.
     * @return array<string, Table>
     */
    public static function loadList(string $projectId, bool $manualOrder = false, bool $orderByLabel = false, bool $rowCounts = false, string $assigneeId = null): array
    {
        $sql = "SELECT t.*";
        $params = [];

        if ($rowCounts) {
            $sql .= ", (SELECT COUNT(*) FROM `dbc_row` r WHERE r.`table_id` = t.`id` AND r.`deleted` IS FALSE) AS `rowCount`";
            $sql .= ", (SELECT COUNT(*) FROM `dbc_row` r WHERE r.`table_id` = t.`id` AND r.`valid` IS FALSE AND r.`deleted` IS FALSE) AS `invalidCount`";
            $sql .= ", (SELECT COUNT(*) FROM `dbc_row` r WHERE r.`table_id` = t.`id` AND r.`flagged` IS TRUE) AS `flaggedCount`";
        }

        if ($assigneeId !== null) {
            $sql .= ", (SELECT COUNT(*) FROM `dbc_row` r WHERE r.`table_id` = t.`id` AND r.`assignee_id` = ?) AS `assignedCount`";
            $params[] = $assigneeId;
        }

        $sql .= " FROM `dbc_table` t WHERE `project_id`=? ORDER BY `".($manualOrder ? "position" : ($orderByLabel ? "label" : "name"))."`";
        $params[] = $projectId;

        MySQLConnection::$instance->execute($sql, $params);
        $result = MySQLConnection::$instance->getSelectedRows();
        $list = [];

        foreach ($result as $row) {
            $table = new Table($row);
            $list[$table->id] = $table;
        }

        return $list;
    }

    /** @var string */
    public $id;

    /** @var string */
    public $projectId;

    /** @var string */
    public $name;

    /** @var string */
    public $label;

    /** @var string|null */
    public $instructions;

    /** @var string */
    public $position;

    /** @var string */
    public $created;

    /** @var int|null */
    public $rowCount;

    /** @var int|null */
    public $invalidCount;

    /** @var int|null */
    public $flaggedCount;

    /** @var int|null */
    public $assignedCount;

    /**
     * @param array<string, string> $data
     */
    public function __construct(array $data)
    {
        $this->id = $data["id"];
        $this->projectId = $data["project_id"];
        $this->name = $data["name"];
        $this->label = $data["label"];
        $this->instructions = $data["instructions"];
        $this->position = $data["position"];
        $this->created = $data["created"];

        if (isset($data["rowCount"])) {
            $this->rowCount = (int) $data["rowCount"];
        }

        if (isset($data["invalidCount"])) {
            $this->invalidCount = (int) $data["invalidCount"];
        }

        if (isset($data["flaggedCount"])) {
            $this->flaggedCount = (int) $data["flaggedCount"];
        }

        if (isset($data["assignedCount"])) {
            $this->assignedCount = (int) $data["assignedCount"];
        }
    }

    public function edit(string $name, string $label, string $instructions = null)
    {
        MySQLConnection::$instance->execute("UPDATE `dbc_table` SET `label`=?, `name`=?, `instructions`=? WHERE `id`=?", [$label, $name, $instructions, $this->id]);
        $this->label = $label;
        $this->name = $name;
        $this->instructions = $instructions;
    }

    public function move(int $newPosition)
    {
        $oldPosition = intval($this->position);

        if ($oldPosition > $newPosition) {
            // move down
            MySQLConnection::$instance->execute("UPDATE `dbc_table` SET `position`=`position`+1 WHERE `project_id`=? AND `position`<? AND `position`>=?", [$this->projectId, $oldPosition, $newPosition]);
        } else {
            // move up
            MySQLConnection::$instance->execute("UPDATE `dbc_table` SET `position`=`position`-1 WHERE `project_id`=? AND `position`>? AND `position`<=?", [$this->projectId, $oldPosition, $newPosition]);
        }

        MySQLConnection::$instance->execute("UPDATE `dbc_table` SET `position`=? WHERE `id`=?", [$newPosition, $this->id]);
        $this->position = (string) $newPosition;
    }
}
