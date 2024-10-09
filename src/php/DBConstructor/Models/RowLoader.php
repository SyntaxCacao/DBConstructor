<?php

declare(strict_types=1);

namespace DBConstructor\Models;

use DBConstructor\SQL\MySQLConnection;

class RowLoader
{
    const FILTER_ASSIGNEE_ANYONE = "anyone";

    const FILTER_DELETED_EXCLUDE = "0";

    const FILTER_DELETED_INCLUDE = "1";

    const FILTER_DELETED_ONLY = "2";

    const FILTER_FLAGGED = "0";

    const FILTER_FLAGGED_COMMENTED = "1";

    const FILTER_VALIDITY_INVALID = "0";

    const FILTER_VALIDITY_VALID = "1";

    const ORDER_BY_CREATED = "0";

    const ORDER_BY_LAST_ACTIVITY = "1";

    const ORDER_DIRECTION_ASCENDING = "0";

    const ORDER_DIRECTION_DESCENDING = "1";

    const SEARCH_COLUMN_ALL_TEXTUAL = "all";

    const SEARCH_COLUMN_EXPORT_ID = "exportid";

    const SEARCH_COLUMN_ID = "id";

    /** @var string|null */
    public $assignee;

    /** @var string|null */
    public $createdAfter;

    /** @var string|null */
    public $createdBefore;

    /** @var string|null */
    public $creator;

    /** @var string */
    public $deleted = self::FILTER_DELETED_EXCLUDE;

    /** @var string|null */
    public $flagged;

    /** @var string */
    public $order = self::ORDER_BY_LAST_ACTIVITY;

    /** @var string */
    public $orderDirection = self::ORDER_DIRECTION_DESCENDING;

    /** @var int */
    public $rowsPerPage = 20;

    /** @var string|null */
    protected $searchColumn;

    /** @var bool|null */
    protected $searchColumnRelational;

    /** @var string|null */
    protected $searchValue;

    /** @var string */
    public $tableId;

    /** @var string|null */
    public $updatedBy;

    /** @var string|null */
    public $validity;

    public function __construct(string $tableId)
    {
        $this->tableId = $tableId;
    }

    public function addSearch(string $column, string $value = null)
    {
        if ($column === self::SEARCH_COLUMN_ID || $column === self::SEARCH_COLUMN_EXPORT_ID) {
            $this->searchColumn = $column;

            if (intval($value) > 0) {
                $this->searchValue = $value;
            }

            return;
        }

        if ($column === self::SEARCH_COLUMN_ALL_TEXTUAL) {
            $this->searchColumn = $column;
            $this->searchColumnRelational = false;

            if ($value !== null && $value !== "") {
                $this->searchValue = $value;
            }
        }

        $matches = [];

        if (preg_match("/^(txt|rel)-([1-9]+\d*)$/", $column, $matches)) {
            $this->searchColumnRelational = $matches[1] === "rel";
            $this->searchColumn = $matches[2];

            if ($value !== null && $value !== "") {
                $this->searchValue = $value;
            }
        }
    }

    /**
     * @param int|null $page must be set if $isCount is false
     */
    protected function buildQuery(array &$params, bool $count, int $page = null): string
    {
        // SELECT
        $sql = "SELECT";

        // "distinct" would make the query support multiple search matches in the same row
        // if it was possible to search in multiple columns
        if ($count) {
            $sql .= " COUNT(DISTINCT r.`id`) as `count`";
        } else {
            $sql .= " DISTINCT r.*, ".
                "c.`firstname` AS `creator_firstname`, ".
                "c.`lastname` AS `creator_lastname`, ".
                "l.`firstname` AS `lasteditor_firstname`, ".
                "l.`lastname` AS `lasteditor_lastname`, ".
                "a.`firstname` AS `assignee_firstname`, ".
                "a.`lastname` AS `assignee_lastname`";

            if (intval($this->order) > 0) {
                $sql .= ", o.`value`";
            }
        }

        // FROM
        if ($this->searchColumnRelational === null) {
            $sql .= " FROM `dbc_row` r";
        } else {
            $sql .= " FROM";

            if ($this->searchColumnRelational) {
                $sql .= " `dbc_field_relational` f";
            } else {
                $sql .= " `dbc_field_textual` f";
            }

            $sql .= " LEFT JOIN `dbc_row` r ON r.`id` = f.`row_id`";
        }

        if (! $count) {
            if (intval($this->order) > 0) {
                $sql .= " LEFT JOIN `dbc_field_textual` o ON o.`row_id` = r.`id` AND o.`column_id` = ?";
                $params[] = $this->order;
            }

            $sql .= " LEFT JOIN `dbc_user` c ON c.`id` = r.`creator_id`";
            $sql .= " LEFT JOIN `dbc_user` l ON l.`id` = r.`lasteditor_id`";
            $sql .= " LEFT JOIN `dbc_user` a ON a.`id` = r.`assignee_id`";
        }

        // WHERE
        if ($this->searchColumn === self::SEARCH_COLUMN_ID) {
            // search for exact id
            $sql .= " WHERE r.`table_id`=? AND r.`id`=?";
            $params[] = $this->tableId;
            $params[] = $this->searchValue;
        } else if ($this->searchColumn === self::SEARCH_COLUMN_EXPORT_ID) {
            // search for exact last export id
            $sql .= " WHERE r.`table_id`=? AND r.`exportid`=?";
            $params[] = $this->tableId;
            $params[] = $this->searchValue;
        } else if ($this->searchColumn === self::SEARCH_COLUMN_ALL_TEXTUAL) {
            // search in all textual fields
            $sql .= " WHERE r.`table_id`=? AND f.`value`";
            $params[] = $this->tableId;

            if ($this->searchValue === null) {
                $sql .= " IS NULL";
            } else {
                $sql .= " LIKE ?";
                $params[] = "%".$this->searchValue."%";
            }
        } else if ($this->searchColumn !== null) {
            // search
            $sql .= " WHERE f.`column_id`=?";
            $params[] = $this->searchColumn;

            if ($this->searchColumnRelational) {
                $sql .= " AND f.`target_row_id`";
            } else {
                $sql .= " AND f.`value`";
            }

            if ($this->searchValue === null) {
                $sql .= " IS NULL";
            } else {
                if ($this->searchColumnRelational) {
                    $sql .= "=?";
                    $params[] = $this->searchValue;
                } else {
                    $sql .= " LIKE ?";
                    $params[] = "%".$this->searchValue."%";
                }
            }
        } else {
            // DEFAULT CASE: filter table
            $sql .= " WHERE r.`table_id`=?";
            $params[] = $this->tableId;
        }

        // validity
        if ($this->validity === self::FILTER_VALIDITY_VALID) {
            $sql .= " AND r.`valid`=TRUE";
        } else if ($this->validity === self::FILTER_VALIDITY_INVALID) {
            $sql .= " AND r.`valid`=FALSE";
        }

        // flagged
        if ($this->flagged === self::FILTER_FLAGGED) {
            $sql .= " AND r.`flagged`=TRUE";
        } else if ($this->flagged === self::FILTER_FLAGGED_COMMENTED) {
            $sql .= " AND (SELECT COUNT(*) FROM `dbc_row_action` ac WHERE ac.`row_id` = r.`id` AND ac.`action`='".RowAction::ACTION_COMMENT."') > 0";
        }

        // assignee
        if ($this->assignee === self::FILTER_ASSIGNEE_ANYONE) {
            $sql .= " AND r.`assignee_id` IS NOT NULL";
        } else if ($this->assignee !== null) {
            $sql .= " AND r.`assignee_id`=?";
            $params[] = $this->assignee;
        }

        // creator
        if ($this->creator !== null) {
            $sql .= " AND r.`creator_id`=?";
            $params[] = $this->creator;
        }

        // deleted
        if ($this->deleted === self::FILTER_DELETED_EXCLUDE) {
            $sql .= " AND r.`deleted`=FALSE";
        } else if ($this->deleted === self::FILTER_DELETED_ONLY) {
            $sql .= " AND r.`deleted`=TRUE";
        }

        // created after
        $matches = [];
        if ($this->createdAfter !== null &&
            preg_match("/^(\d{4})-(\d{1,2})-(\d{1,2})$/", $this->createdAfter, $matches) &&
            checkdate(intval($matches[2]), intval($matches[3]), intval($matches[1]))) {
            $sql .= " AND r.`created` >= ?";
            $params[] = $this->createdAfter;
        }

        // created before
        $matches = [];
        if ($this->createdBefore !== null &&
            preg_match("/^(\d{4})-(\d{1,2})-(\d{1,2})$/", $this->createdBefore, $matches) &&
            checkdate(intval($matches[2]), intval($matches[3]), intval($matches[1]))) {
            $sql .= " AND r.`created` <= ?";
            $params[] = $this->createdBefore;
        }

        // updated by
        if (intval($this->updatedBy) > 0) {
            $sql .= " AND (SELECT COUNT(*) FROM `dbc_row_action` ac WHERE ac.`row_id` = r.`id` AND ac.`action`='".RowAction::ACTION_CHANGE."' AND ac.`user_id`=?) > 0";
            $params[] = $this->updatedBy;
        }

        // ORDER BY
        if (! $count) {
            if ($this->order === self::ORDER_BY_LAST_ACTIVITY) {
                $sql .= " ORDER BY r.`lastupdated`";

                if ($this->orderDirection === self::ORDER_DIRECTION_DESCENDING) {
                    $sql .= " DESC";
                }

                // for when lastupdated is exactly the same, e.g. when multiple rows
                // are created with one API call
                $sql .= ", r.`id`";

                if ($this->orderDirection === self::ORDER_DIRECTION_DESCENDING) {
                    $sql .= " DESC";
                }
            } else if ($this->order === self::ORDER_BY_CREATED) {
                $sql .= " ORDER BY r.`id`";

                if ($this->orderDirection === self::ORDER_DIRECTION_DESCENDING) {
                    $sql .= " DESC";
                }
            } else if (intval($this->order) > 0) {
                $sql .= " ORDER BY o.`value`";

                if ($this->orderDirection === self::ORDER_DIRECTION_DESCENDING) {
                    $sql .= " DESC";
                }
            }
        }

        // LIMIT
        if (! $count) {
            $sql .= " LIMIT ".($page - 1) * $this->rowsPerPage.", ".$this->rowsPerPage;
        }

        return $sql;
    }

    public function calcPages(int $rowCount): int
    {
        return intval(ceil($rowCount / $this->rowsPerPage));
    }

    public function getRowCount(): int
    {
        $params = [];
        MySQLConnection::$instance->execute($this->buildQuery($params, true), $params);
        return intval(MySQLConnection::$instance->getSelectedRows()[0]["count"]);
    }

    /**
     * @return array<string, Row>
     */
    public function getRows(int $page): array
    {
        $params = [];
        MySQLConnection::$instance->execute($this->buildQuery($params, false, $page), $params);
        $result = MySQLConnection::$instance->getSelectedRows();
        $list = [];

        foreach ($result as $row) {
            $obj = new Row($row);
            $list[$obj->id] = $obj;
        }

        return $list;
    }
}
