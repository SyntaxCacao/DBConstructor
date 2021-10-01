<?php

declare(strict_types=1);

namespace DBConstructor\Models;

use DBConstructor\SQL\MySQLConnection;

class Row
{
    public static function create(string $tableId, string $creatorId): string
    {
        MySQLConnection::$instance->execute("INSERT INTO `dbc_row` (`table_id`, `creator_id`) VALUES (?, ?)", [$tableId, $creatorId]);

        return MySQLConnection::$instance->getLastInsertId();

        // TODO: insert into row_action
    }

    /**
     * @return Row|null
     */
    public static function load(string $id)
    {
        MySQLConnection::$instance->execute("SELECT * FROM `dbc_row` WHERE `id`=?", [$id]);
        $result = MySQLConnection::$instance->getSelectedRows();

        if (count($result) != 1) {
            return null;
        }

        return new Row($result[0]);
    }

    /**
     * @return Row[]
     */
    public static function loadList(string $tableId, bool $forExport = false): array
    {
        if ($forExport) {
            MySQLConnection::$instance->execute("SELECT * FROM `dbc_row` WHERE `table_id`=? AND `deleted`=FALSE ORDER BY `exportid`", [$tableId]);
        } else {
            MySQLConnection::$instance->execute("SELECT * FROM `dbc_row` WHERE `table_id`=? AND `deleted`=FALSE ORDER BY `lastupdated` DESC", [$tableId]);
        }

        $result = MySQLConnection::$instance->getSelectedRows();
        $list = [];

        foreach ($result as $row) {
            $list[] = new Row($row);
        }

        return $list;
    }

    public static function setExportId(string $tableId)
    {
        MySQLConnection::$instance->execute("UPDATE `dbc_row` SET `exportid`=null WHERE `table_id`=?", [$tableId]);
        MySQLConnection::$instance->execute("SET @i=0; UPDATE `dbc_row` SET `exportid`=@i:=@i+1 WHERE `table_id`=? AND `deleted`=FALSE ORDER BY `created`", [$tableId]);
    }

    public static function setValidity(string $rowId, bool $valid = null)
    {
        MySQLConnection::$instance->execute("UPDATE `dbc_row` SET `valid`=? WHERE `id`=?", [intval($valid), $rowId]);
    }

    /** @var string */
    public $id;

    /** @var string */
    public $tableId;

    /** @var string */
    public $creatorId;

    /** @var string|null */
    public $assigneeId;

    /** @var string|null */
    public $lasteditorId;

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
    public $lastupdated;

    /**
     * @param string[] $data
     */
    public function __construct(array $data)
    {
        $this->id = $data["id"];
        $this->tableId = $data["table_id"];
        $this->creatorId = $data["creator_id"];
        $this->assigneeId = $data["assignee_id"];
        $this->lasteditorId = $data["lasteditor_id"];

        if ($data["valid"] !== null) {
            $this->valid = $data["valid"] == "1";
        }

        $this->flagged = $data["flagged"] == "1";
        $this->deleted = $data["deleted"] == "1";
        $this->exportId = $data["exportid"];
        $this->created = $data["created"];
        $this->lastupdated = $data["lastupdated"];
    }
}
