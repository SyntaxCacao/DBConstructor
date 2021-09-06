<?php

declare(strict_types=1);

namespace DBConstructor\Models;

use DBConstructor\SQL\MySQLConnection;

class Table
{
    /**
     * @param string|null $description
     */
    public static function create(string $projectId, string $name, string $label, $description): string
    {
        MySQLConnection::$instance->execute("SELECT `position` FROM `dbc_table` WHERE `project_id`=? ORDER BY `position` DESC LIMIT 1", [$projectId]);

        $result = MySQLConnection::$instance->getSelectedRows();
        $position = 1;

        if (count($result) > 0) {
            $position = intval($result[0]["position"]) + 1;
        }

        MySQLConnection::$instance->execute("INSERT INTO `dbc_table` (`project_id`, `name`, `label`, `description`, `position`) VALUES (?, ?, ?, ?, ?)", [$projectId, $name, $label, $description, $position]);

        return MySQLConnection::$instance->getLastInsertId();
    }

    public static function isNameAvailable(string $name): bool
    {
        MySQLConnection::$instance->execute("SELECT COUNT(*) AS `count` FROM `dbc_table` WHERE `name`=?", [$name]);
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

    public static function loadList(string $projectId): array
    {
        MySQLConnection::$instance->execute("SELECT t.*, (SELECT COUNT(*) FROM `dbc_row` r WHERE r.`table_id` = t.`id` AND r.`deleted` = FALSE) AS `count` FROM `dbc_table` t WHERE `project_id`=? ORDER BY `position`", [$projectId]);
        $result = MySQLConnection::$instance->getSelectedRows();
        $list = [];

        foreach ($result as $row) {
            $list[] = ["obj" => new Table($row), "rows" => $row["count"]];
        }

        return $list;
    }

    /**
     * @return Table[]
     */
    public static function loadListAbove(string $projectId, string $position): array
    {
        MySQLConnection::$instance->execute("SELECT * FROM `dbc_table` WHERE `project_id`=? AND `position`<? ORDER BY `position`", [$projectId, $position]);
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
    public $description;

    /** @var string */
    public $position;

    /** @var string */
    public $created;

    /**
     * @param string[] $data
     */
    public function __construct(array $data)
    {
        $this->id = $data["id"];
        $this->projectId = $data["project_id"];
        $this->name = $data["name"];
        $this->label = $data["label"];
        $this->description = $data["description"];
        $this->position = $data["position"];
        $this->created = $data["created"];
    }

    public function edit(string $name, string $label, string $description = null)
    {
        MySQLConnection::$instance->execute("UPDATE `dbc_table` SET `label`=?, `name`=?, `description`=? WHERE `id`=?", [$label, $name, $description, $this->id]);
        $this->label = $label;
        $this->name = $name;
        $this->description = $description;
    }
}
