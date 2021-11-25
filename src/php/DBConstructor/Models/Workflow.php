<?php

declare(strict_types=1);

namespace DBConstructor\Models;

use DBConstructor\SQL\MySQLConnection;

class Workflow
{
    public static function create(string $projectId, string $creatorId, string $label, string $description = null): string
    {
        MySQLConnection::$instance->execute("INSERT INTO `dbc_workflow` (`project_id`, `lasteditor_id`, `label`, `description`) VALUES (?, ?, ?, ?)", [$projectId, $creatorId, $label, $description]);
        return MySQLConnection::$instance->getLastInsertId();
    }

    /**
     * @return Workflow|null
     */
    public static function load(string $id, bool $loadLastEditor = false)
    {
        if ($loadLastEditor) {
            // @formatter:off
            $sql = "SELECT `workflow`.*, ".
                          "`lasteditor`.`firstname` as `lasteditor_firstname`, ".
                          "`lasteditor`.`lastname` as `lasteditor_lastname` ".
                   "FROM `dbc_workflow` `workflow` ".
                   "LEFT JOIN `dbc_user` `lasteditor` ON `workflow`.`lasteditor_id` = `lasteditor`.`id` ".
                   "WHERE `workflow`.`id`=?";
            // @formatter:on
        } else {
            $sql = "SELECT * FROM `dbc_workflow` WHERE `id`=?";
        }

        MySQLConnection::$instance->execute($sql, [$id]);
        $result = MySQLConnection::$instance->getSelectedRows();

        if (count($result) !== 1) {
            return null;
        }

        return new Workflow($result[0]);
    }

    /**
     * @return array<Workflow>
     */
    public static function loadList(string $projectId, bool $onlyActive): array
    {
        if ($onlyActive) {
            $sql = "SELECT * FROM `dbc_workflow` WHERE `project_id`=? AND `active`=TRUE ORDER BY `label`";
        } else {
            $sql = "SELECT * FROM `dbc_workflow` WHERE `project_id`=? ORDER BY `label`";
        }

        MySQLConnection::$instance->execute($sql, [$projectId]);

        $result = MySQLConnection::$instance->getSelectedRows();
        $list = [];

        foreach ($result as $workflow) {
            $list[] = new Workflow($workflow);
        }

        return $list;
    }

    /** @var string */
    public $id;

    /** @var string */
    public $lastEditorId;

    /** @var string */
    public $lastEditorFirstName;

    /** @var string */
    public $lastEditorLastName;

    /** @var string */
    public $label;

    /** @var string */
    public $description;

    /** @var bool */
    public $active;

    /** @var string */
    public $lastUpdated;

    /** @var string */
    public $created;

    /**
     * @param array<string> $data
     */
    public function __construct(array $data)
    {
        $this->id = $data["id"];
        $this->lastEditorId = $data["lasteditor_id"];
        $this->label = $data["label"];
        $this->description = $data["description"];
        $this->active = $data["active"] == "1";
        $this->lastUpdated = $data["lastupdated"];
        $this->created = $data["created"];

        if (isset($data["lasteditor_firstname"])) {
            $this->lastEditorFirstName = $data["lasteditor_firstname"];
        }

        if (isset($data["lasteditor_lastname"])) {
            $this->lastEditorLastName = $data["lasteditor_lastname"];
        }
    }

    public function activate()
    {
        MySQLConnection::$instance->execute("UPDATE `dbc_workflow` SET `active`=TRUE WHERE `id`=?", [$this->id]);
        $this->active = true;
    }

    public function deactivate()
    {
        MySQLConnection::$instance->execute("UPDATE `dbc_workflow` SET `active`=FALSE WHERE `id`=?", [$this->id]);
        $this->active = false;
    }

    public function edit(string $userId, string $label, string $description = null)
    {
        MySQLConnection::$instance->execute("UPDATE `dbc_workflow` SET `label`=?, `description`=? WHERE `id`=?", [$label, $description, $this->id]);
        $this->setUpdated($userId);
        $this->label = $label;
        $this->description = $description;
    }

    public function setUpdated(string $userId)
    {
        MySQLConnection::$instance->execute("UPDATE `dbc_workflow` SET `lasteditor_id`=?, `lastupdated`=CURRENT_TIMESTAMP WHERE `id`=?", [$userId, $this->id]);
        $this->updateLastUpdated();
    }

    public function updateLastUpdated()
    {
        // @formatter:off
        MySQLConnection::$instance->execute("SELECT `workflow`.`lasteditor_id`, ".
                                                       "`workflow`.`lastupdated`, ".
                                                       "`lasteditor`.`firstname` as `lasteditor_firstname`, ".
                                                       "`lasteditor`.`lastname` as `lasteditor_lastname` ".
                                                "FROM `dbc_workflow` `workflow` ".
                                                "LEFT JOIN `dbc_user` `lasteditor` ON `workflow`.`lasteditor_id` = `lasteditor`.`id` ".
                                                "WHERE `workflow`.`id`=?", [$this->id]);
        // @formatter:on

        $row = MySQLConnection::$instance->getSelectedRows()[0];
        $this->lastEditorId = $row["lasteditor_id"];
        $this->lastEditorFirstName = $row["lasteditor_firstname"];
        $this->lastEditorLastName = $row["lasteditor_lastname"];
        $this->lastUpdated = $row["lastupdated"];
    }
}
