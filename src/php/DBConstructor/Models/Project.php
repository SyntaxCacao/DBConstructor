<?php

declare(strict_types=1);

namespace DBConstructor\Models;

use DBConstructor\SQL\MySQLConnection;

class Project
{
    /**
     * @param string|null $description
     */
    public static function create(string $label, $description): string
    {
        MySQLConnection::$instance->execute("INSERT INTO `dbc_project` (`label`, `description`) VALUES (?, ?)", [$label, $description]);

        return MySQLConnection::$instance->getLastInsertId();
    }

    /**
     * @return Project|null
     */
    public static function load(string $id)
    {
        MySQLConnection::$instance->execute("SELECT * FROM `dbc_project` WHERE `id`=?", [$id]);
        $result = MySQLConnection::$instance->getSelectedRows();

        if (count($result) != 1) {
            return null;
        }

        return new Project($result[0]);
    }

    /**
     * @return Project[]
     */
    public static function loadList(): array
    {
        MySQLConnection::$instance->execute("SELECT * FROM `dbc_project` ORDER BY `label`");
        $result = MySQLConnection::$instance->getSelectedRows();
        $list = [];

        foreach ($result as $row) {
            $list[] = new Project($row);
        }

        return $list;
    }

    /** @var string */
    public $id;

    /** @var string */
    public $label;

    /** @var string|null */
    public $description;

    /** @var string */
    public $created;

    /**
     * @param string[] $data
     */
    public function __construct(array $data)
    {
        $this->id = $data["id"];
        $this->label = $data["label"];
        $this->description = $data["description"];
        $this->created = $data["created"];
    }

    /**
     * @param string|null $description
     */
    public function edit(string $label, $description)
    {
        MySQLConnection::$instance->execute("UPDATE `dbc_project` SET `label`=?, `description`=? WHERE `id`=?", [$label, $description, $this->id]);
        $this->label = $label;
        $this->description = $description;
    }
}
