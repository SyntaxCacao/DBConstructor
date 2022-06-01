<?php

declare(strict_types=1);

namespace DBConstructor\Models;

use DBConstructor\SQL\MySQLConnection;

class Project
{
    public static function create(string $label, string $description = null, string $notes = null): string
    {
        MySQLConnection::$instance->execute("INSERT INTO `dbc_project` (`label`, `description`, `notes`) VALUES (?, ?, ?)", [$label, $description, $notes]);

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
     * @return array<string, Project>
     */
    public static function loadAll(): array
    {
        return Project::loadList("SELECT * FROM `dbc_project` ORDER BY `label`");
    }

    /**
     * @return array<string, Project>
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
     * @return array<string, Project>
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

    /** @var string|null */
    public $notes;

    /** @var bool */
    public $manualOrder;

    /** @var string */
    public $created;

    /**
     * @param array<string, string> $data
     */
    public function __construct(array $data)
    {
        $this->id = $data["id"];
        $this->mainPageId = $data["mainpage_id"];
        $this->label = $data["label"];
        $this->description = $data["description"];
        $this->notes = $data["notes"];
        $this->manualOrder = $data["manualorder"] === "1";
        $this->created = $data["created"];
    }

    public function edit(string $label, string $description = null, string $notes = null, bool $manualOrder)
    {
        MySQLConnection::$instance->execute("UPDATE `dbc_project` SET `label`=?, `description`=?, `notes`=?, `manualorder`=? WHERE `id`=?", [$label, $description, $notes, intval($manualOrder), $this->id]);
        $this->label = $label;
        $this->description = $description;
        $this->notes = $notes;
        $this->manualOrder = $manualOrder;
    }

    public function setMainPage(string $pageId)
    {
        MySQLConnection::$instance->execute("UPDATE `dbc_project` SET `mainpage_id`=? WHERE `id`=?", [$pageId, $this->id]);
        $this->mainPageId = $pageId;
    }
}
