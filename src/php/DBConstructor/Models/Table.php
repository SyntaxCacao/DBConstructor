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
     * @param bool $orderByLabel List will be ordered by labels if true, by names if false.
     * @return array<Table>
     */
    public static function loadList(string $projectId, bool $orderByLabel = false): array
    {
        MySQLConnection::$instance->execute("SELECT t.*, (SELECT COUNT(*) FROM `dbc_row` r WHERE r.`table_id` = t.`id` AND r.`deleted` = FALSE) AS `count` FROM `dbc_table` t WHERE `project_id`=? ORDER BY `".($orderByLabel ? "label" : "name")."`", [$projectId]);
        $result = MySQLConnection::$instance->getSelectedRows();
        $list = [];

        foreach ($result as $row) {
            $list[] = new Table($row);
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

        if (isset($data["count"])) {
            $this->rowCount = $data["count"];
        }
    }

    public function edit(string $name, string $label, string $instructions = null)
    {
        MySQLConnection::$instance->execute("UPDATE `dbc_table` SET `label`=?, `name`=?, `instructions`=? WHERE `id`=?", [$label, $name, $instructions, $this->id]);
        $this->label = $label;
        $this->name = $name;
        $this->instructions = $instructions;
    }
}
