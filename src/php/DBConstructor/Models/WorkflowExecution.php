<?php

declare(strict_types=1);

namespace DBConstructor\Models;

use DBConstructor\SQL\MySQLConnection;

class WorkflowExecution
{
    /** @var int */
    const ROWS_PER_PAGE = 20;

    public static function calcPages(int $count): int
    {
        return intval(ceil($count / self::ROWS_PER_PAGE));
    }

    public static function count(string $workflowId): int
    {
        MySQLConnection::$instance->execute("SELECT COUNT(*) AS `count` FROM `dbc_workflow_execution` WHERE `workflow_id`=?", [$workflowId]);
        return intval(MySQLConnection::$instance->getSelectedRows()[0]["count"]);
    }

    /**
     * @param array<string, string> $rows key = step id, value = row id
     */
    public static function create(string $workflowId, string $userId, array $rows)
    {
        MySQLConnection::$instance->execute("INSERT INTO `dbc_workflow_execution` (`workflow_id`, `user_id`) VALUES (?, ?)", [$workflowId, $userId]);
        $id = MySQLConnection::$instance->getLastInsertId();
        WorkflowExecutionRow::createAll($id, $rows);
    }

    /**
     * @return array<WorkflowExecution>
     */
    public static function loadList(string $workflowId, int $page): array
    {
        // @formatter:off
        MySQLConnection::$instance->execute("SELECT `execution`.*, ".
                                                       "`user`.`firstname` AS `user_firstname`, ".
                                                       "`user`.`lastname` AS `user_lastname` ".
                                                "FROM `dbc_workflow_execution` `execution` ".
                                                "LEFT JOIN `dbc_user` `user` ON `execution`.`user_id` = `user`.`id` ".
                                                "WHERE `execution`.`workflow_id`=? ".
                                                "ORDER BY `execution`.`created` DESC ".
                                                "LIMIT ".(($page-1)*WorkflowExecution::ROWS_PER_PAGE).", ".WorkflowExecution::ROWS_PER_PAGE, [$workflowId]);
        // @formatter:on
        $result = MySQLConnection::$instance->getSelectedRows();
        $list = [];

        foreach ($result as $row) {
            $list[] = new WorkflowExecution($row);
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
    public $created;

    /**
     * @param array<string, string> $data
     */
    public function __construct(array $data)
    {
        $this->id = $data["id"];
        $this->userId = $data["user_id"];
        $this->userFirstName = $data["user_firstname"];
        $this->userLastName = $data["user_lastname"];
        $this->created = $data["created"];
    }
}
