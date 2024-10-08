<?php

declare(strict_types=1);

namespace DBConstructor\Models;

use DBConstructor\SQL\MySQLConnection;
use Exception;
use PDOStatement;

class Row
{
    const MAX_COMMENT_LENGTH = 2000;

    public static function count(string $tableId): int
    {
        MySQLConnection::$instance->execute("SELECT COUNT(*) AS `count` FROM `dbc_row` WHERE `table_id`=?", [$tableId]);
        return (int) MySQLConnection::$instance->getSelectedRows()[0]["count"];
    }

    public static function countInvalidInProject(string $projectId): int
    {
        MySQLConnection::$instance->execute("SELECT COUNT(*) AS `count` FROM `dbc_row` WHERE `table_id` IN (SELECT id FROM `dbc_table` WHERE `project_id`=?) AND `valid` IS FALSE AND `deleted` IS FALSE", [$projectId]);
        return (int) MySQLConnection::$instance->getSelectedRows()[0]["count"];
    }

    public static function countValidInProject(string $projectId): int
    {
        MySQLConnection::$instance->execute("SELECT COUNT(*) AS `count` FROM `dbc_row` WHERE `table_id` IN (SELECT id FROM `dbc_table` WHERE `project_id`=?) AND `valid` IS TRUE AND `deleted` IS FALSE", [$projectId]);
        return (int) MySQLConnection::$instance->getSelectedRows()[0]["count"];
    }

    public static function create(string $tableId, string $creatorId, bool $api, string $comment = null, bool $flagged, string $assigneeId = null): string
    {
        MySQLConnection::$instance->execute("INSERT INTO `dbc_row` (`table_id`, `creator_id`, `lasteditor_id`, `assignee_id`, `flagged`, `api`) VALUES (?, ?, ?, ?, ?, ?)", [$tableId, $creatorId, $creatorId, $assigneeId, intval($flagged), intval($api)]);
        $id = MySQLConnection::$instance->getLastInsertId();

        RowAction::logCreation($id, $creatorId, $api);

        if ($comment !== null) {
            RowAction::logComment($id, $creatorId, $api, $comment);
        }

        if ($flagged) {
            RowAction::logFlag($id, $creatorId, $api);
        }

        if ($assigneeId !== null) {
            RowAction::logAssignment($id, $creatorId, $api, $assigneeId);
        }

        return $id;
    }

    public static function isTableEmpty(string $tableId): bool
    {
        MySQLConnection::$instance->execute("SELECT COUNT(*) <= 0 AS `isEmpty` FROM `dbc_row` WHERE `table_id`=?", [$tableId]);
        return boolval(MySQLConnection::$instance->getSelectedRows()[0]["isEmpty"]);
    }

    /**
     * @return Row|null
     */
    public static function load(string $id)
    {
        // @formatter:off
        MySQLConnection::$instance->execute("SELECT `row`.*, ".
                                                        "`lasteditor`.`firstname` as `lasteditor_firstname`, ".
                                                        "`lasteditor`.`lastname` as `lasteditor_lastname`, ".
                                                        "`assignee`.`firstname` as `assignee_firstname`, ".
                                                        "`assignee`.`lastname` as `assignee_lastname` ".
                                                 "FROM `dbc_row` `row` ".
                                                 "LEFT JOIN `dbc_user` `lasteditor` ON `row`.`lasteditor_id` = `lasteditor`.`id` ".
                                                 "LEFT JOIN `dbc_user` `assignee` ON `row`.`assignee_id` = `assignee`.`id` ".
                                                 "WHERE `row`.`id`=?", [$id]);
        $result = MySQLConnection::$instance->getSelectedRows();
        // @formatter:on

        if (count($result) != 1) {
            return null;
        }

        return new Row($result[0]);
    }

    /**
     * @return array<string, Row>
     */
    public static function loadReferencing(string $rowId): array
    {
        MySQLConnection::$instance->execute("SELECT * FROM `dbc_row` WHERE `id` IN (SELECT `row_id` FROM `dbc_field_relational` WHERE `target_row_id`=?)", [$rowId]);
        $result = MySQLConnection::$instance->getSelectedRows();
        $list = [];

        foreach ($result as $row) {
            $obj = new Row($row);
            $list[$obj->id] = $obj;
        }

        return $list;
    }

    /**
     * Loads Row object and projectId, checks if user participates in project,
     * but does not load names for lastEditor and assignee.
     *
     * @return Row|null
     */
    public static function loadWithProjectId(string $userId, string $rowId, &$projectId, &$isParticipant)
    {
        MySQLConnection::$instance->execute("SELECT r.*, t.`project_id`, ".
            "EXISTS(SELECT * FROM `dbc_participant` p WHERE p.`user_id`=? AND p.`project_id` = t.`project_id` AND p.`removed` IS NULL) AS `isParticipant` ".
            "FROM `dbc_row` r ".
            "LEFT JOIN `dbc_table` t ON r.`table_id` = t.`id` ".
            "WHERE r.`id`=?", [$userId, $rowId]);
        $result = MySQLConnection::$instance->getSelectedRows();

        if (count($result) != 1) {
            return null;
        }

        $projectId = $result[0]["project_id"];
        $isParticipant = $result[0]["isParticipant"] === "1";
        return new Row($result[0]);
    }

    public static function revalidate(string $id)
    {
        MySQLConnection::$instance->execute("UPDATE `dbc_row` SET `valid` = ((SELECT COUNT(*) FROM `dbc_field_relational` WHERE `row_id`=? AND `valid` = FALSE) = 0 AND (SELECT COUNT(*) FROM `dbc_field_textual` WHERE `row_id`=? AND `valid` != TRUE) = 0) WHERE `id`=?", [$id, $id, $id]);
        RelationalField::revalidateReferencing($id);
    }

    public static function revalidateAllInvalid(string $tableId)
    {
        // TODO one query instead of executing queries in foreach
        MySQLConnection::$instance->execute("SELECT `id` FROM `dbc_row` WHERE `table_id`=? AND `valid`=FALSE", [$tableId]);
        $result = MySQLConnection::$instance->getSelectedRows();

        foreach ($result as $row) {
            Row::revalidate($row["id"]);
        }
    }

    public static function revalidateAllValid(string $tableId)
    {
        // TODO one query instead of executing queries in foreach
        MySQLConnection::$instance->execute("SELECT `id` FROM `dbc_row` WHERE `table_id`=? AND `valid`=TRUE", [$tableId]);
        $result = MySQLConnection::$instance->getSelectedRows();

        foreach ($result as $row) {
            Row::revalidate($row["id"]);
        }
    }

    public static function selectListExport(string $tableId): PDOStatement
    {
        return MySQLConnection::$instance->executeSeparately("SELECT * FROM `dbc_row` WHERE `table_id`=? AND `exportid` IS NOT NULL ORDER BY `exportid`", [$tableId]);
    }

    public static function setExportId(string $tableId)
    {
        MySQLConnection::$instance->execute("UPDATE `dbc_row` SET `exportid`=NULL WHERE `table_id`=?", [$tableId]);
        MySQLConnection::$instance->execute("SET @i=0; UPDATE `dbc_row` SET `exportid`=@i:=@i+1 WHERE `table_id`=? AND `valid`=TRUE AND `deleted`=FALSE ORDER BY `created`", [$tableId]);
    }

    /** @var string */
    public $id;

    /** @var string */
    public $tableId;

    /** @var string */
    public $creatorId;

    /** @var string */
    public $creatorFirstName;

    /** @var string */
    public $creatorLastName;

    /** @var string */
    public $lastEditorId;

    /** @var string */
    public $lastEditorFirstName;

    /** @var string */
    public $lastEditorLastName;

    /** @var string|null */
    public $assigneeId;

    /** @var string|null */
    public $assigneeFirstName;

    /** @var string|null */
    public $assigneeLastName;

    /** @var bool|null */
    public $valid;

    /** @var bool */
    public $flagged;

    /** @var bool */
    public $deleted;

    /** @var string|null */
    public $exportId;

    /** @var string */
    public $created;

    /** @var string */
    public $lastUpdated;

    /**
     * @param array<string, string> $data
     */
    public function __construct(array $data)
    {
        $this->id = $data["id"];
        $this->tableId = $data["table_id"];
        $this->creatorId = $data["creator_id"];
        $this->lastEditorId = $data["lasteditor_id"];
        $this->assigneeId = $data["assignee_id"];
        $this->valid = $data["valid"] == "1";
        $this->flagged = $data["flagged"] == "1";
        $this->deleted = $data["deleted"] == "1";
        $this->exportId = $data["exportid"];
        $this->created = $data["created"];
        $this->lastUpdated = $data["lastupdated"];

        if (isset($data["creator_firstname"])) {
            $this->creatorFirstName = $data["creator_firstname"];
        }

        if (isset($data["creator_lastname"])) {
            $this->creatorLastName = $data["creator_lastname"];
        }

        if (isset($data["lasteditor_firstname"])) {
            $this->lastEditorFirstName = $data["lasteditor_firstname"];
        }

        if (isset($data["lasteditor_lastname"])) {
            $this->lastEditorLastName = $data["lasteditor_lastname"];
        }

        if (isset($data["assignee_firstname"])) {
            $this->assigneeFirstName = $data["assignee_firstname"];
        }

        if (isset($data["assignee_lastname"])) {
            $this->assigneeLastName = $data["assignee_lastname"];
        }
    }

    public function assign(string $userId, bool $api, string $assigneeId = null, string $assigneeFirstName = null, string $assigneeLastName = null)
    {
        MySQLConnection::$instance->execute("UPDATE `dbc_row` SET `assignee_id`=?, `lasteditor_id`=?, `lastupdated`=CURRENT_TIMESTAMP WHERE `id`=?", [$assigneeId, $userId, $this->id]);
        $this->assigneeId = $assigneeId;
        $this->assigneeFirstName = $assigneeFirstName;
        $this->assigneeLastName = $assigneeLastName;
        $this->updateLastUpdated();
        RowAction::logAssignment($this->id, $userId, $api, $assigneeId);
    }

    public function comment(string $userId, bool $api, string $comment): string
    {
        MySQLConnection::$instance->execute("UPDATE `dbc_row` SET `lasteditor_id`=?, `lastupdated`=CURRENT_TIMESTAMP WHERE `id`=?", [$userId, $this->id]);
        $this->updateLastUpdated();
        return RowAction::logComment($this->id, $userId, $api, $comment);
    }

    public function delete(string $userId, bool $api)
    {
        MySQLConnection::$instance->execute("UPDATE `dbc_row` SET `deleted`=TRUE, `lasteditor_id`=?, `lastupdated`=CURRENT_TIMESTAMP WHERE `id`=?", [$userId, $this->id]);
        $this->deleted = true;
        $this->updateLastUpdated();
        RelationalField::revalidateReferencing($this->id);
        RowAction::logDeletion($this->id, $userId, $api);
    }

    /**
     * @throws Exception
     */
    public function deletePermanently(string $userId, string $projectId)
    {
        RowAttachment::deleteRow($projectId, $this->tableId, $this->id);
        RowAction::deleteRow($this->id);
        RelationalField::deleteRow($this->id);
        TextualField::deleteRow($this->id);
        RelationalField::nullifyReferencing($userId, $this);
        MySQLConnection::$instance->execute("DELETE FROM `dbc_row` WHERE `id`=?", [$this->id]);
    }

    public function flag(string $userId, bool $api)
    {
        MySQLConnection::$instance->execute("UPDATE `dbc_row` SET `flagged`=TRUE, `lasteditor_id`=?, `lastupdated`=CURRENT_TIMESTAMP WHERE `id`=?", [$userId, $this->id]);
        $this->flagged = true;
        $this->updateLastUpdated();
        RowAction::logFlag($this->id, $userId, $api);
    }

    public function restore(string $userId, bool $api)
    {
        MySQLConnection::$instance->execute("UPDATE `dbc_row` SET `deleted`=FALSE, `lasteditor_id`=?, `lastupdated`=CURRENT_TIMESTAMP WHERE `id`=?", [$userId, $this->id]);
        $this->deleted = false;
        $this->updateLastUpdated();
        RelationalField::revalidateReferencing($this->id);
        RowAction::logRestoration($this->id, $userId, $api);
    }

    public function setUpdated(string $userId)
    {
        MySQLConnection::$instance->execute("UPDATE `dbc_row` SET `lasteditor_id`=?, `lastupdated`=CURRENT_TIMESTAMP WHERE `id`=?", [$userId, $this->id]);
        $this->updateLastUpdated();
    }

    public function unflag(string $userId, bool $api)
    {
        MySQLConnection::$instance->execute("UPDATE `dbc_row` SET `flagged`=FALSE, `lasteditor_id`=?, `lastupdated`=CURRENT_TIMESTAMP WHERE `id`=?", [$userId, $this->id]);
        $this->flagged = false;
        $this->updateLastUpdated();
        RowAction::logUnflag($this->id, $userId, $api);
    }

    public function updateLastUpdated()
    {
        // @formatter:off
        MySQLConnection::$instance->execute("SELECT `row`.`lasteditor_id`, ".
                                                        "`row`.`lastupdated`, ".
                                                        "`lasteditor`.`firstname` as `lasteditor_firstname`, ".
                                                        "`lasteditor`.`lastname` as `lasteditor_lastname` ".
                                                 "FROM `dbc_row` `row` ".
                                                 "LEFT JOIN `dbc_user` `lasteditor` ON `row`.`lasteditor_id` = `lasteditor`.`id` ".
                                                 "WHERE `row`.`id`=?", [$this->id]);
        // @formatter:on

        $row = MySQLConnection::$instance->getSelectedRows()[0];
        $this->lastEditorId = $row["lasteditor_id"];
        $this->lastEditorFirstName = $row["lasteditor_firstname"];
        $this->lastEditorLastName = $row["lasteditor_lastname"];
        $this->lastUpdated = $row["lastupdated"];
    }

    public function updateValidity()
    {
        MySQLConnection::$instance->execute("SELECT `valid` FROM `dbc_row` WHERE `id`=?", [$this->id]);
        $row = MySQLConnection::$instance->getSelectedRows()[0];
        $this->valid = $row["valid"] === "1";
    }
}
