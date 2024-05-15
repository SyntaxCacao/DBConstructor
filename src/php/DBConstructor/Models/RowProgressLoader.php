<?php

declare(strict_types=1);

namespace DBConstructor\Models;

use DateInterval;
use DateTime;
use DBConstructor\Controllers\Projects\ProjectsController;
use DBConstructor\SQL\MySQLConnection;
use Exception;

class RowProgressLoader
{
    const PERIOD_ALLTIME = 0;

    const PERIOD_YEAR = 1;

    const PERIOD_MONTH_6 = 2;

    const PERIOD_MONTH_3 = 3;

    const PERIODS = [
        self::PERIOD_ALLTIME => null,
        self::PERIOD_YEAR => "P1Y",
        self::PERIOD_MONTH_6 => "P6M",
        self::PERIOD_MONTH_3 => "P3M"
    ];

    const TOTAL_ALLTIME = 0;

    const TOTAL_THIS_WEEK = 1;

    const TOTAL_LAST_WEEK = 2;

    /**
     * @throws Exception
     */
    public static function loadProgress(string $projectId, string $tableId = null, string $userId = null, bool $includeApi = true, int $period = self::PERIOD_ALLTIME): array
    {
        $params = [];
        // Using ANY_VALUE even though firstday and lastday will always be the same to avoid issues
        // with MySQL installations with ONLY_FULL_GROUP_BY mode
        // https://dev.mysql.com/doc/refman/8.0/en/group-by-handling.html
        // Using now MIN instead of ANY_VALUE for MariaDB compatibility
        // https://stackoverflow.com/a/54173805/5489107
        // @formatter:off
        $sql = "SELECT YEARWEEK(`created`, 7) AS `yearweek`, ".
                      "MIN(DATE_ADD(DATE(`created`), INTERVAL -WEEKDAY(`created`) DAY)) AS `firstday`, ".
                      "MIN(DATE_ADD(DATE_ADD(DATE(`created`), INTERVAL -WEEKDAY(`created`) DAY), INTERVAL 6 DAY)) AS `lastday`, ".
                      "COUNT(*) AS `records` ".
               "FROM `dbc_row` ".
               "WHERE ";
        // @formatter:on

        if ($tableId === null) {
            $sql .= "`table_id` IN (SELECT `id` FROM `dbc_table` WHERE `project_id`=?) ";
            $params[] = $projectId;
        } else {
            $sql .= "`table_id`=? ";
            $params[] = $tableId;
        }

        if ($userId !== null) {
            $sql .= "AND `creator_id`=? ";
            $params[] = $userId;
        }

        if (! $includeApi) {
            $sql .= "AND `api` IS FALSE ";
        }

        /*
        if (self::PERIODS_SQL_INTERVALS[$period] !== null) {
            $sql .= "AND YEARWEEK(`created`, 7) >= YEARWEEK(DATE_ADD(CURRENT_DATE, INTERVAL -".self::PERIODS_SQL_INTERVALS[$period]."), 7) ";
        }
        */

        $sql .= "AND `deleted` IS FALSE ";
        $sql .= "GROUP BY `yearweek` ";
        $sql .= "ORDER BY `yearweek`";

        MySQLConnection::$instance->execute($sql, $params);
        $result = MySQLConnection::$instance->getSelectedRows();

        $weeks = [];

        $weekInterval = new DateInterval("P7D");
        $lastDayInterval = new DateInterval("P6D");

        $beginDate = null;

        if (self::PERIODS[$period] !== null) {
            $todayDate = (new DateTime((new DateTime())->format("Y-m-d")));
            $beginDate = $todayDate->sub(new DateInterval(self::PERIODS[$period]))->sub($lastDayInterval);
        }

        $lastWeekFirstDay = null;
        $recordsTotal = 0;

        foreach ($result as $row) {
            $firstDay = new DateTime($row["firstday"]);

            if ($beginDate !== null && $firstDay < $beginDate) {
                $recordsTotal += intval($row["records"]);
                continue;
            }

            if ($lastWeekFirstDay !== null) {
                // Insert gap weeks with no progress
                while ($lastWeekFirstDay->add($weekInterval) < $firstDay) {
                    $year = intval($lastWeekFirstDay->format("o"));
                    $week = intval($lastWeekFirstDay->format("W"));

                    $weeks["$year-$week"] = [
                        "year" => $year,
                        "week" => $week,
                        "firstDay" => $lastWeekFirstDay->format("Y-m-d"),
                        "lastDay" => (clone($lastWeekFirstDay))->add($lastDayInterval)->format("Y-m-d"),
                        "recordsAdded" => 0,
                        "recordsTotal" => $recordsTotal
                    ];
                }
            }

            $year = intval(substr($row["yearweek"], 0, 4));
            $week = intval(substr($row["yearweek"], 4, 2));
            $recordsAdded = intval($row["records"]);
            $recordsTotal += $recordsAdded;

            $weeks["$year-$week"] = [
                "year" => $year,
                "week" => $week,
                "firstDay" => $row["firstday"],
                "lastDay" => $row["lastday"],
                "recordsAdded" => $recordsAdded,
                "recordsTotal" => $recordsTotal
            ];

            $lastWeekFirstDay = $firstDay;
        }

        return $weeks;
    }

    /**
     * @throws Exception
     */
    public static function loadProgressPerUser(string $projectId, string $tableId = null, bool $includeApi = true, int $period = self::PERIOD_ALLTIME): array
    {
        $params = [];
        // Using ANY_VALUE even though firstday and lastday will always be the same to avoid issues
        // with MySQL installations with ONLY_FULL_GROUP_BY mode
        // https://dev.mysql.com/doc/refman/8.0/en/group-by-handling.html
        // Now using MIN instead of ANY_VALUE for MariaDB compatibility
        // https://stackoverflow.com/a/54173805/5489107
        // @formatter:off
        $sql = "SELECT YEARWEEK(`created`, 7) AS `yearweek`, ".
                      "MIN(DATE_ADD(DATE(`created`), INTERVAL -WEEKDAY(`created`) DAY)) AS `firstday`, ".
                      "MIN(DATE_ADD(DATE_ADD(DATE(`created`), INTERVAL -WEEKDAY(`created`) DAY), INTERVAL 6 DAY)) AS `lastday`, ".
                      "`creator_id`, ".
                      "COUNT(*) AS `records` ".
               "FROM `dbc_row` ".
               "WHERE ";
        // @formatter:on

        if ($tableId === null) {
            $sql .= "`table_id` IN (SELECT `id` FROM `dbc_table` WHERE `project_id`=?) ";
            $params[] = $projectId;
        } else {
            $sql .= "`table_id`=? ";
            $params[] = $tableId;
        }

        if (! $includeApi) {
            $sql .= "AND `api` IS FALSE ";
        }

        $sql .= "AND `deleted` IS FALSE ";
        $sql .= "GROUP BY `yearweek`, `creator_id` ";
        $sql .= "ORDER BY `yearweek`, `creator_id`";

        MySQLConnection::$instance->execute($sql, $params);
        $result = MySQLConnection::$instance->getSelectedRows();

        $progressData = [
            "weeks" => [],
            "users" => []
        ];

        $participants = Participant::loadList(ProjectsController::$projectId, true);

        $weekInterval = new DateInterval("P7D");
        $lastDayInterval = new DateInterval("P6D");

        $beginDate = null;

        if (self::PERIODS[$period] !== null) {
            $todayDate = (new DateTime((new DateTime())->format("Y-m-d")));
            $beginDate = $todayDate->sub(new DateInterval(self::PERIODS[$period]))->sub($lastDayInterval);
        }

        // Look for weeks and insert non-zero progress data
        $lastWeekFirstDay = null;

        foreach ($result as $row) {
            $firstDay = new DateTime($row["firstday"]);

            if ($beginDate !== null && $firstDay < $beginDate) {
                continue;
            }

            if (! isset($progressData["users"][$row["creator_id"]])) {
                $progressData["users"][$row["creator_id"]] = [
                    "label" => $participants[$row["creator_id"]]->firstName." ".$participants[$row["creator_id"]]->lastName,
                    "weeks" => []
                ];
            }

            if ($lastWeekFirstDay !== null) {
                // Insert gap weeks with no progress
                while ($lastWeekFirstDay->add($weekInterval) < $firstDay) {
                    $year = intval($lastWeekFirstDay->format("o"));
                    $week = intval($lastWeekFirstDay->format("W"));
                    $weekPadded = str_pad((string) $week, 2, "0", STR_PAD_LEFT);

                    $progressData["weeks"]["$year-$weekPadded"] = [
                        "year" => $year,
                        "week" => $week,
                        "firstDay" => $lastWeekFirstDay->format("Y-m-d"),
                        "lastDay" => (clone($lastWeekFirstDay))->add($lastDayInterval)->format("Y-m-d")
                    ];
                }
            }

            $lastWeekFirstDay = $firstDay;

            $year = intval(substr($row["yearweek"], 0, 4));
            $weekPadded = substr($row["yearweek"], 4, 2);
            $week = intval($weekPadded);
            $yearweek = "$year-$weekPadded";

            if (! isset($progressData["weeks"][$yearweek])) {
                $progressData["weeks"][$yearweek] = [
                    "year" => $year,
                    "week" => $week,
                    "firstDay" => $row["firstday"],
                    "lastDay" => $row["lastday"]
                ];
            }

            $progressData["users"][$row["creator_id"]]["weeks"][$yearweek] = intval($row["records"]);
        }

        return $progressData;
    }

    public static function loadTotal(string $projectId, string $userId = null, int $period = self::TOTAL_ALLTIME): int
    {
        $params = [$projectId];

        $sql = "SELECT COUNT(*) AS `count` ".
            "FROM `dbc_row` ".
            "WHERE `table_id` IN (SELECT `id` FROM `dbc_table` WHERE `project_id`=?) ";

        if ($userId !== null) {
            $sql .= "AND `creator_id`=? ";
            $params[] = $userId;
        }

        if ($period === self::TOTAL_THIS_WEEK) {
            $sql .= "AND YEARWEEK(`created`, 7) >= YEARWEEK(CURRENT_DATE, 7) ";
        } else if ($period === self::TOTAL_LAST_WEEK) {
            $sql .= "AND YEARWEEK(`created`, 7) = YEARWEEK(DATE_ADD(CURRENT_DATE, INTERVAL -7 DAY), 7) ";
        }

        $sql .= "AND `deleted` IS FALSE";

        MySQLConnection::$instance->execute($sql, $params);
        return intval(MySQLConnection::$instance->getSelectedRows()[0]["count"]);
    }

    public static function loadTotalPerUser(string $projectId, string $tableId = null, bool $includeApi = true, string $endDate = null): array
    {
        $sql = "SELECT `creator_id`, ".
            "COUNT(*) AS `count` ".
            "FROM `dbc_row` ".
            "WHERE ";

        if ($tableId === null) {
            $sql .= "`table_id` IN (SELECT `id` FROM `dbc_table` WHERE `project_id`=?) ";
            $params = [$projectId];
        } else {
            $sql .= "`table_id`=? ";
            $params = [$tableId];
        }

        if (! $includeApi) {
            $sql .= "AND `api` IS FALSE ";
        }

        if ($endDate !== null) {
            $sql .= "AND `created` <= DATE_ADD(?, INTERVAL 1 DAY) ";
            $params[] = $endDate;
        }

        $sql .= "AND `deleted` IS FALSE GROUP BY `creator_id`";

        MySQLConnection::$instance->execute($sql, $params);
        $result = MySQLConnection::$instance->getSelectedRows();
        $data = [];

        foreach ($result as $row) {
            $data[$row["creator_id"]] = intval($row["count"]);
        }

        return $data;
    }
}
