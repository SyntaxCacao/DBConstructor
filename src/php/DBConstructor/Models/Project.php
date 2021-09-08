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
    public static function loadAll(): array
    {
        return Project::loadList("SELECT * FROM `dbc_project` ORDER BY `label`");
    }

    /**
     * @return Project[]
     */
    private static function loadList(string $sql, array $params = []): array
    {
        MySQLConnection::$instance->execute($sql, $params);
        $result = MySQLConnection::$instance->getSelectedRows();
        $list = [];

        foreach ($result as $row) {
            $project = new Project($row);
            $list[$project->id] = new Project($row);
        }

        return $list;
    }

    /**
     * @return Project[]
     */
    public static function loadParticipating(string $userId): array
    {
        return Project::loadList("SELECT pr.* FROM `dbc_project` pr LEFT JOIN `dbc_participant` pa ON pr.`id` = pa.`project_id` WHERE pa.`user_id`=? ORDER BY pr.`label`", [$userId]);
    }

    /** @var string */
    public $id;

    /** @var string|null */
    public $mainPageId;

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
        $this->mainPageId = $data["mainpage_id"];
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

    public function setMainPage(string $pageId)
    {
        MySQLConnection::$instance->execute("UPDATE `dbc_project` SET `mainpage_id`=? WHERE `id`=?", [$pageId, $this->id]);
        $this->mainPageId = $pageId;
    }
}
