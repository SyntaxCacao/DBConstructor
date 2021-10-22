<?php

declare(strict_types=1);

namespace DBConstructor\Models;

use DBConstructor\SQL\MySQLConnection;

class Participant
{
    public static function countManagers(string $projectId): int
    {
        MySQLConnection::$instance->execute("SELECT COUNT(*) AS `count` FROM `dbc_participant` WHERE `project_id`=? AND `manager`=TRUE", [$projectId]);
        return intval(MySQLConnection::$instance->getSelectedRows()[0]["count"]);
    }

    public static function create(string $userId, string $projectId, bool $manager): string
    {
        MySQLConnection::$instance->execute("INSERT INTO `dbc_participant` (`user_id`, `project_id`, `manager`) VALUES (?, ?, ?)", [$userId, $projectId, intval($manager)]);

        return MySQLConnection::$instance->getLastInsertId();
    }

    /**
     * @return Participant|null
     */
    public static function load(string $sql, array $params)
    {
        MySQLConnection::$instance->execute($sql, $params);
        $result = MySQLConnection::$instance->getSelectedRows();

        if (count($result) != 1) {
            return null;
        }

        return new Participant($result[0]);
    }

    /**
     * @return Participant|null
     */
    public static function loadFromId(string $projectId, string $participantId)
    {
        return Participant::load("SELECT * FROM `dbc_participant` WHERE `id`=? AND `project_id`=?", [$participantId, $projectId]);
    }

    /**
     * @return Participant|null
     */
    public static function loadFromUser(string $projectId, string $userId)
    {
        return Participant::load("SELECT * FROM `dbc_participant` WHERE `user_id`=? AND `project_id`=?", [$userId, $projectId]);
    }

    /**
     * @return array<Participant>
     */
    public static function loadList(string $projectId): array
    {
        MySQLConnection::$instance->execute("SELECT p.*, u.`firstname` AS `user_firstname`, u.`lastname` AS `user_lastname`, u.`locked` AS `user_locked` FROM `dbc_participant` p LEFT JOIN `dbc_user` u ON p.`user_id`=u.`id` WHERE p.`project_id`=? ORDER BY p.`manager` DESC, u.`lastname`, u.`firstname`", [$projectId]);
        $result = MySQLConnection::$instance->getSelectedRows();
        $list = [];

        foreach ($result as $row) {
            $list[] = new Participant($row);
        }

        return $list;
    }

    /** @var string */
    public $id;

    /** @var string */
    public $userId;

    /** @var string|null */
    public $firstName;

    /** @var string|null */
    public $lastName;

    /** @var bool|null */
    public $locked;

    /** @var string */
    public $projectId;

    /** @var bool */
    public $isManager;

    /** @var string */
    public $added;

    /**
     * @param array<string, string> $data
     */
    public function __construct(array $data)
    {
        $this->id = $data["id"];
        $this->userId = $data["user_id"];

        if (isset($data["user_firstname"])) {
            $this->firstName = $data["user_firstname"];
        }

        if (isset($data["user_lastname"])) {
            $this->lastName = $data["user_lastname"];
        }

        if (isset($data["user_locked"])) {
            $this->locked = $data["user_locked"] == "1";
        }

        $this->projectId = $data["project_id"];
        $this->isManager = $data["manager"] == "1";
        $this->added = $data["added"];
    }

    public function delete()
    {
        MySQLConnection::$instance->execute("DELETE FROM `dbc_participant` WHERE `id`=?", [$this->id]);
    }

    public function demote()
    {
        if ($this->isManager) {
            MySQLConnection::$instance->execute("UPDATE `dbc_participant` SET `manager`=FALSE WHERE `id`=?", [$this->id]);
            $this->isManager = false;
        }
    }

    public function promote()
    {
        if (! $this->isManager) {
            MySQLConnection::$instance->execute("UPDATE `dbc_participant` SET `manager`=TRUE WHERE `id`=?", [$this->id]);
            $this->isManager = true;
        }
    }
}
