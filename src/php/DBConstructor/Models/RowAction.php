<?php

declare(strict_types=1);

namespace DBConstructor\Models;

use DBConstructor\SQL\MySQLConnection;
use DBConstructor\Util\JsonException;

class RowAction
{
    const ACTION_ASSIGNMENT = "assignment";

    const ACTION_CHANGE = "change";

    const ACTION_COMMENT = "comment";

    const ACTION_CREATION = "creation";

    const ACTION_DELETION = "deletion";

    const ACTION_FLAG = "flag";

    const ACTION_RESTORATION = "restoration";

    const ACTION_UNFLAG = "unflag";

    const CHANGE_DATA_COLUMN_ID = "col";

    const CHANGE_DATA_IS_RELATIONAL = "isRel";

    const CHANGE_DATA_NEW_VALUE = "new";

    const CHANGE_DATA_PREVIOUS_VALUE = "prev";

    public static function deleteRow(string $rowId)
    {
        MySQLConnection::$instance->execute("DELETE FROM `dbc_row_action` WHERE `row_id`=?", [$rowId]);
    }

    /**
     * @return array<RowAction>
     */
    public static function loadAll(string $rowId): array
    {
        // @formatter:off
        //$t = "(SELECT * FROM `dbc_row_action` WHERE `action` <> 'assignment' OR `data` IS NULL) UNION (SELECT a.`id`, a.`row_id`, a.`user_id`, a.`action`, CONCAT_WS(' ', u.`firstname`, u.`lastname`) AS `data`, a.`created` FROM `dbc_row_action` a LEFT JOIN `dbc_user` u ON a.`data`= u.`id` WHERE a.`action` = 'assignment' AND a.`data` IS NOT NULL) ORDER BY `id`"
        MySQLConnection::$instance->execute("(SELECT a.*, ".
                                                         "u.`firstname` AS `user_firstname`, ".
                                                         "u.`lastname` AS `user_lastname` ".
                                                  "FROM `dbc_row_action` a ".
                                                  "LEFT JOIN `dbc_user` u ON a.`user_id` = u.`id` ".
                                                  "WHERE a.`row_id`=? AND (a.`action`<>'".RowAction::ACTION_ASSIGNMENT."' OR a.`data` IS NULL OR a.`data`=a.`user_id`)) ".
                                              "UNION ".
                                                 "(SELECT a.`id`, ".
                                                         "a.`row_id`, ".
                                                         "a.`user_id`, ".
                                                         "a.`action`, ".
                                                         "CONCAT_WS(' ', assignee.`firstname`, assignee.`lastname`), ".
                                                         "a.`created`, ".
                                                         "u.`firstname` AS `user_firstname`, ".
                                                         "u.`lastname` AS `user_lastname` ".
                                                  "FROM `dbc_row_action` a ".
                                                  "LEFT JOIN `dbc_user` u ON a.`user_id`=u.`id` ".
                                                  "LEFT JOIN `dbc_user` assignee ON a.`data`=assignee.`id` ".
                                                  "WHERE a.`row_id`=? AND a.`action`='".RowAction::ACTION_ASSIGNMENT."' AND a.`data` IS NOT NULL AND a.`data`<>a.`user_id`) ".
                                              "ORDER BY `id`", [$rowId, $rowId]);
        // @formatter:on
        $result = MySQLConnection::$instance->getSelectedRows();
        $list = [];

        foreach ($result as $row) {
            $list[] = new RowAction($row);
        }

        return $list;
    }

    protected static function log(string $action, string $rowId, string $userId, string $data = null)
    {
        MySQLConnection::$instance->execute("INSERT INTO `dbc_row_action` (`row_id`, `user_id`, `action`, `data`) VALUES (?, ?, ?, ?)", [$rowId, $userId, $action, $data]);
    }

    public static function logAssignment(string $rowId, string $userId, string $assigneeId = null)
    {
        RowAction::log(RowAction::ACTION_ASSIGNMENT, $rowId, $userId, $assigneeId);
    }

    /**
     * @throws JsonException
     */
    public static function logChange(string $rowId, string $userId, bool $isRelational, string $columnId, string $prevValue = null, string $newValue = null)
    {
        $array = [
            RowAction::CHANGE_DATA_COLUMN_ID => $columnId,
            RowAction::CHANGE_DATA_IS_RELATIONAL => $isRelational,
            RowAction::CHANGE_DATA_PREVIOUS_VALUE => $prevValue,
            RowAction::CHANGE_DATA_NEW_VALUE => $newValue
        ];

        $json = json_encode($array);

        if ($json === false) {
            throw new JsonException();
        }

        RowAction::log(RowAction::ACTION_CHANGE, $rowId, $userId, json_encode($array));
    }

    public static function logComment(string $rowId, string $userId, string $comment)
    {
        RowAction::log(RowAction::ACTION_COMMENT, $rowId, $userId, $comment);
    }

    public static function logCreation(string $rowId, string $userId)
    {
        RowAction::log(RowAction::ACTION_CREATION, $rowId, $userId);
    }

    public static function logDeletion(string $rowId, string $userId)
    {
        RowAction::log(RowAction::ACTION_DELETION, $rowId, $userId);
    }

    public static function logFlag(string $rowId, string $userId)
    {
        RowAction::log(RowAction::ACTION_FLAG, $rowId, $userId);
    }

    public static function logRestoration(string $rowId, string $userId)
    {
        RowAction::log(RowAction::ACTION_RESTORATION, $rowId, $userId);
    }

    public static function logUnflag(string $rowId, string $userId)
    {
        RowAction::log(RowAction::ACTION_UNFLAG, $rowId, $userId);
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
    public $action;

    /** @var string|null */
    public $data;

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
        $this->action = $data["action"];
        $this->created = $data["created"];

        if ($this->action == RowAction::ACTION_CHANGE) {
            $this->data = json_decode($data["data"], true);
        } else {
            $this->data = $data["data"];
        }
    }
}
