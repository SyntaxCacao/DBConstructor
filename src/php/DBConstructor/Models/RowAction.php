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

    const ACTION_REDIRECTION_DESTINATION = "redirection_dest";

    const ACTION_REDIRECTION_ORIGIN = "redirection_origin";

    const ACTION_RESTORATION = "restoration";

    const ACTION_UNFLAG = "unflag";

    const CHANGE_DATA_COLUMN_ID = "col";

    const CHANGE_DATA_IS_RELATIONAL = "isRel";

    const CHANGE_DATA_NEW_VALUE = "new";

    const CHANGE_DATA_PREVIOUS_VALUE = "prev";

    const COMMENT_DATA_TEXT = "text";

    /**
     * Optional. Assumed to be `false` if not set.
     */
    const COMMENT_DATA_EDITED = "edit";

    /**
     * Optional. Assumed to be `false` if not set.
     */
    const COMMENT_DATA_EXCLUDE_EXPORT = "excludeExport";

    const REDIRECTION_DATA_COUNT = "count";

    const REDIRECTION_DATA_DESTINATION = "dest";

    const REDIRECTION_DATA_ORIGIN = "origin";

    public static function deleteRow(string $rowId)
    {
        MySQLConnection::$instance->execute("DELETE FROM `dbc_row_action` WHERE `row_id`=?", [$rowId]);
    }

    public static function load(string $id)
    {
        MySQLConnection::$instance->execute("SELECT a.*, u.`firstname` AS `user_firstname`, u.`lastname` AS `user_lastname` FROM `dbc_row_action` a LEFT JOIN `dbc_user` u ON a.`user_id`=u.`id` WHERE a.`id`=?", [$id]);
        $result = MySQLConnection::$instance->getSelectedRows();

        if (count($result) !== 1) {
            return null;
        }

        return new RowAction($result[0]);
    }

    /**
     * @return array<RowAction>
     */
    public static function loadAll(string $rowId, bool $filter = false): array
    {
        // @formatter:off
        //$t = "(SELECT * FROM `dbc_row_action` WHERE `action` <> 'assignment' OR `data` IS NULL) UNION (SELECT a.`id`, a.`row_id`, a.`user_id`, a.`action`, CONCAT_WS(' ', u.`firstname`, u.`lastname`) AS `data`, a.`created` FROM `dbc_row_action` a LEFT JOIN `dbc_user` u ON a.`data`= u.`id` WHERE a.`action` = 'assignment' AND a.`data` IS NOT NULL) ORDER BY `id`"
        MySQLConnection::$instance->execute("(SELECT a.*, ".
                                                         "u.`firstname` AS `user_firstname`, ".
                                                         "u.`lastname` AS `user_lastname` ".
                                                  "FROM `dbc_row_action` a ".
                                                  "LEFT JOIN `dbc_user` u ON a.`user_id` = u.`id` ".
                                                  "WHERE a.`row_id`=?".($filter ? " AND a.`action`<>'".RowAction::ACTION_CHANGE."'" : "")." AND (a.`action`<>'".RowAction::ACTION_ASSIGNMENT."' OR a.`data` IS NULL OR a.`data`=a.`user_id`)) ".
                                              "UNION ".
                                                 "(SELECT a.`id`, ".
                                                         "a.`row_id`, ".
                                                         "a.`user_id`, ".
                                                         "a.`action`, ".
                                                         "CONCAT_WS(' ', assignee.`firstname`, assignee.`lastname`), ".
                                                         "a.`api`, ".
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

    /**
     * Raw = assigneeIds won't be resolved into names
     *
     * @return array<RowAction>
     */
    public static function loadAllRaw(string $rowId): array
    {
        MySQLConnection::$instance->execute("SELECT a.*, u.`firstname` AS `user_firstname`, u.`lastname` AS `user_lastname` FROM `dbc_row_action` a LEFT JOIN `dbc_user` u ON a.`user_id` = u.`id` WHERE a.`row_id`=?", [$rowId]);
        $result = MySQLConnection::$instance->getSelectedRows();
        $list = [];

        foreach ($result as $row) {
            $list[] = new RowAction($row);
        }

        return $list;
    }

    protected static function log(string $action, string $rowId, string $userId, bool $api, string $data = null)
    {
        MySQLConnection::$instance->execute("INSERT INTO `dbc_row_action` (`row_id`, `user_id`, `action`, `data`, `api`) VALUES (?, ?, ?, ?, ?)", [$rowId, $userId, $action, $data, intval($api)]);
    }

    public static function logAssignment(string $rowId, string $userId, bool $api, string $assigneeId = null)
    {
        RowAction::log(RowAction::ACTION_ASSIGNMENT, $rowId, $userId, $api, $assigneeId);
    }

    public static function logChange(string $rowId, string $userId, bool $api, bool $isRelational, string $columnId, string $prevValue = null, string $newValue = null)
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

        RowAction::log(RowAction::ACTION_CHANGE, $rowId, $userId, $api, json_encode($array));
    }

    public static function logComment(string $rowId, string $userId, bool $api, string $comment): string
    {
        $data = json_encode([
            RowAction::COMMENT_DATA_TEXT => $comment
        ]);

        if ($data === false) {
            throw new JsonException();
        }

        RowAction::log(RowAction::ACTION_COMMENT, $rowId, $userId, $api, $data);
        return MySQLConnection::$instance->getLastInsertId();
    }

    public static function logCreation(string $rowId, string $userId, bool $api)
    {
        RowAction::log(RowAction::ACTION_CREATION, $rowId, $userId, $api);
    }

    public static function logDeletion(string $rowId, string $userId, bool $api)
    {
        RowAction::log(RowAction::ACTION_DELETION, $rowId, $userId, $api);
    }

    public static function logFlag(string $rowId, string $userId, bool $api)
    {
        RowAction::log(RowAction::ACTION_FLAG, $rowId, $userId, $api);
    }

    public static function logRedirection(string $userId, bool $api, string $originId, string $destinationId, int $count)
    {
        // origin
        $data = json_encode([
            RowAction::REDIRECTION_DATA_DESTINATION => intval($destinationId),
            RowAction::REDIRECTION_DATA_COUNT => $count
        ]);

        if ($data === false) {
            throw new JsonException();
        }

        RowAction::log(RowAction::ACTION_REDIRECTION_ORIGIN, $originId, $userId, $api, $data);

        // destination
        $data = json_encode([
            RowAction::REDIRECTION_DATA_ORIGIN => intval($originId),
            RowAction::REDIRECTION_DATA_COUNT => $count
        ]);

        if ($data === false) {
            throw new JsonException();
        }

        RowAction::log(RowAction::ACTION_REDIRECTION_DESTINATION, $destinationId, $userId, $api, $data);
    }

    public static function logRestoration(string $rowId, string $userId, bool $api)
    {
        RowAction::log(RowAction::ACTION_RESTORATION, $rowId, $userId, $api);
    }

    public static function logUnflag(string $rowId, string $userId, bool $api)
    {
        RowAction::log(RowAction::ACTION_UNFLAG, $rowId, $userId, $api);
    }

    /** @var string */
    public $id;

    /** @var string */
    public $rowId;

    /** @var string */
    public $userId;

    /** @var string */
    public $userFirstName;

    /** @var string */
    public $userLastName;

    /** @var string */
    public $action;

    /** @var array|string|null */
    public $data;

    /** @var bool */
    public $api;

    /** @var string */
    public $created;

    /**
     * @param array<string, string> $data
     */
    public function __construct(array $data)
    {
        $this->id = $data["id"];
        $this->rowId = $data["row_id"];
        $this->userId = $data["user_id"];
        $this->userFirstName = $data["user_firstname"];
        $this->userLastName = $data["user_lastname"];
        $this->action = $data["action"];
        $this->api = $data["api"] === "1";
        $this->created = $data["created"];

        if ($this->action == RowAction::ACTION_CHANGE ||
            $this->action == RowAction::ACTION_COMMENT ||
            $this->action == RowAction::ACTION_REDIRECTION_DESTINATION ||
            $this->action == RowAction::ACTION_REDIRECTION_ORIGIN) {
            $this->data = json_decode($data["data"], true);

            if ($this->data === false) {
                throw new JsonException();
            }
        } else {
            $this->data = $data["data"];
        }
    }

    public function delete()
    {
        MySQLConnection::$instance->execute("DELETE FROM `dbc_row_action` WHERE `id`=?", [$this->id]);
    }

    public function editComment(string $text)
    {
        $data = $this->data;
        $data[RowAction::COMMENT_DATA_TEXT] = $text;
        $data[RowAction::COMMENT_DATA_EDITED] = true;
        $json = json_encode($data);

        if ($json === false) {
            throw new JsonException();
        }

        MySQLConnection::$instance->execute("UPDATE `dbc_row_action` SET `data`=? WHERE `id`=?", [$json, $this->id]);
        $this->data = $data;
    }

    public function isCommentEdited(): bool
    {
        return $this->data[RowAction::COMMENT_DATA_EDITED] ?? false;
    }

    public function isCommentExportExcluded(): bool
    {
        return $this->data[RowAction::COMMENT_DATA_EXCLUDE_EXPORT] ?? false;
    }

    public function permitCommentEdit(string $userId, bool $isManager): bool
    {
        return $isManager || $userId === $this->userId;
    }

    public function setCommentExportExcluded(bool $excluded)
    {
        $data = $this->data;

        if ($excluded) {
            $data[RowAction::COMMENT_DATA_EXCLUDE_EXPORT] = true;
        } else {
            if (isset($data[RowAction::COMMENT_DATA_EXCLUDE_EXPORT])) {
                unset($data[RowAction::COMMENT_DATA_EXCLUDE_EXPORT]);
            }
        }

        $json = json_encode($data);

        if ($json === false) {
            throw new JsonException();
        }

        MySQLConnection::$instance->execute("UPDATE `dbc_row_action` SET `data`=? WHERE `id`=?", [$json, $this->id]);
        $this->data = $data;
    }
}
