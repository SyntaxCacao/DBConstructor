<?php

declare(strict_types=1);

namespace DBConstructor\Models;

use DBConstructor\SQL\MySQLConnection;

class WorkflowExecutionRow
{
    /**
     * @param array<string, string> $rows key = step id, value = row id
     */
    public static function createAll(string $executionId, array $rows)
    {
        $sql = "INSERT INTO `dbc_workflow_execution_row` (`execution_id`, `step_id`, `row_id`) VALUES";
        $params = [];
        $first = true;

        foreach ($rows as $stepId => $rowId) {
            if ($first) {
                $first = false;
            } else {
                $sql .= ",";
            }

            $sql .= " (?, ?, ?)";
            $params[] = $executionId;
            $params[] = $stepId;
            $params[] = $rowId;
        }

        MySQLConnection::$instance->execute($sql, $params);
    }

    /**
     * @param array<WorkflowExecution> $executions
     * @return array<string, array<string, WorkflowExecutionRow>>
     */
    public static function loadList(array $executions): array
    {
        $in = "";
        $params = [];
        $first = true;

        foreach ($executions as $execution) {
            if ($first) {
                $first = false;
            } else {
                $in .= ", ";
            }

            $in .= "?";
            $params[] = $execution->id;
        }

        // @formatter:off
        MySQLConnection::$instance->execute("SELECT `execution_row`.`execution_id`, ".
                                                       "`execution_row`.`step_id`, ".
                                                       "`execution_row`.`row_id`, ".
                                                       "`row`.`id` IS NOT NULL AS `row_exists` ".
                                                "FROM `dbc_workflow_execution_row` `execution_row` ".
                                                "LEFT JOIN `dbc_row` `row` ON `execution_row`.`row_id` = `row`.`id` ".
                                                "WHERE `execution_row`.`execution_id` IN ($in)", $params);
        // @formatter:on
        $result = MySQLConnection::$instance->getSelectedRows();
        $table = [];

        foreach ($result as $row) {
            $table[$row["execution_id"]][$row["step_id"]] = new WorkflowExecutionRow($row);
        }

        return $table;
    }

    /** @var string */
    public $stepId;

    /** @var string */
    public $rowId;

    /** @var bool */
    public $rowExists;

    /**
     * @param array<string, string> $data
     */
    public function __construct(array $data)
    {
        $this->stepId = $data["step_id"];
        $this->rowId = $data["row_id"];
        $this->rowExists = $data["row_exists"] === "1";
    }
}
