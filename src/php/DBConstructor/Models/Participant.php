<?php

declare(strict_types=1);

namespace DBConstructor\Models;

use DBConstructor\SQL\MySQLConnection;

class Participant
{
    public static function create(string $userId, string $projectId, bool $manager): string
    {
        MySQLConnection::$instance->execute("INSERT INTO `dbc_participant` (`user_id`, `project_id`, `manager`) VALUES (?, ?, ?)", [$userId, $projectId, intval($manager)]);

        return MySQLConnection::$instance->getLastInsertId();
    }

    public static function delete(string $userId, string $projectId)
    {
        MySQLConnection::$instance->execute("DELETE FROM `dbc_participant` WHERE `user_id`=? AND `project_id=?`", [$userId, $projectId]);
    }

    public static function deleteAll(string $projectId)
    {
        MySQLConnection::$instance->execute("DELETE FROM `dbc_participant` WHERE `project_id=?`", [$projectId]);
    }

    /**
     * @return Participant[]
     */
    public static function loadList(string $projectId): array
    {
        MySQLConnection::$instance->execute("SELECT p.*, u.`firstname` AS `user_firstname`, u.`lastname` AS `user_lastname` FROM `dbc_participant` p LEFT JOIN `dbc_user` u ON p.`user_id`=u.`id` WHERE p.`project_id`=? ORDER BY p.`manager`, u.`lastname`, u.`firstname`", [$projectId]);
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

    /** @var string */
    public $userFirstName;

    /** @var string */
    public $userLastName;

    /** @var string */
    public $projectId;

    /** @var bool */
    public $isManager;

    /** @var string */
    public $added;

    /**
     * @param string[] $data
     */
    public function __construct(array $data)
    {
        $this->id = $data["id"];
        $this->userId = $data["user_id"];
        $this->userFirstName = $data["user_firstname"];
        $this->userLastName = $data["user_lastname"];
        $this->projectId = $data["project_id"];
        $this->isManager = $data["manager"] == "1";
        $this->added = $data["added"];
    }
}
