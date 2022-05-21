<?php

declare(strict_types=1);

namespace DBConstructor\Models;

use DBConstructor\SQL\MySQLConnection;
use DBConstructor\Util\JsonException;

class WorkflowStep
{
    const DATA_KEY_DEPENDING_FIELD = "dependson";

    const DATA_KEY_DEPENDING_VALUE = "value";

    const DATA_KEY_FILL_IN = "step";

    const DATA_KEY_STATIC_VALUE = "value";

    const DATA_KEY_TYPE = "type";

    const DATA_TYPE_DEPENDING = "depending";

    const DATA_TYPE_EXCLUDE = "exclude";

    const DATA_TYPE_FILL_ID = "fillid";

    const DATA_TYPE_INPUT = "input";

    const DATA_TYPE_STATIC = "static";

    const DATA_TYPES = [
        WorkflowStep::DATA_TYPE_DEPENDING => "Bedingte Eingabe",
        WorkflowStep::DATA_TYPE_EXCLUDE => "Ausblenden",
        WorkflowStep::DATA_TYPE_FILL_ID => "ID von früherem Schritt",
        WorkflowStep::DATA_TYPE_INPUT => "Eingabe",
        WorkflowStep::DATA_TYPE_STATIC => "Statischer Wert",
    ];

    public static function create(Workflow $workflow, string $userId, string $tableId): string
    {
        MySQLConnection::$instance->execute("SELECT IFNULL(MAX(`position`), 0)+1 AS `position` FROM `dbc_workflow_step` WHERE `workflow_id`=?", [$workflow->id]);
        $position = MySQLConnection::$instance->getSelectedRows()[0]["position"];
        MySQLConnection::$instance->execute("INSERT INTO `dbc_workflow_step` (`workflow_id`, `table_id`, `position`) VALUES (?, ?, ?)", [$workflow->id, $tableId, $position]);
        $id = MySQLConnection::$instance->getLastInsertId();
        $workflow->setUpdated($userId);
        return $id;
    }

    /**
     * @return WorkflowStep|null
     */
    public static function load(string $id)
    {
        // @formatter:off
        MySQLConnection::$instance->execute("SELECT s.*, ".
                                                       "t.`label` AS `table_label` ".
                                                "FROM `dbc_workflow_step` s ".
                                                "LEFT JOIN `dbc_table` t ON `s`.`table_id` = `t`.`id` ".
                                                "WHERE `s`.`id`=? ", [$id]);
        // @formatter:on
        $result = MySQLConnection::$instance->getSelectedRows();

        if (count($result) !== 1) {
            return null;
        }

        return new WorkflowStep($result[0]);
    }

    /**
     * @return array<string, WorkflowStep>
     */
    public static function loadList(string $workflowId): array
    {
        // @formatter:off
        MySQLConnection::$instance->execute("SELECT s.*, ".
                                                       "t.`label` AS `table_label`, ".
                                                       "t.`description` AS `table_description` ".
                                                "FROM `dbc_workflow_step` s ".
                                                "LEFT JOIN `dbc_table` t ON `s`.`table_id` = `t`.`id` ".
                                                "WHERE `workflow_id`=? ".
                                                "ORDER BY `position`", [$workflowId]);
        // @formatter:on
        $result = MySQLConnection::$instance->getSelectedRows();
        $list = [];

        foreach ($result as $row) {
            $step = new WorkflowStep($row);
            $list[$step->id] = $step;
        }

        return $list;
    }

    /**
     * @param array<RelationalColumn> $relationalColumns
     * @throws JsonException
     */
    public static function readRelationalColumnData(array $relationalColumns, string $data = null): array
    {
        if ($data === null) {
            $rawData = [];
        } else {
            $rawData = json_decode($data, true);

            if ($rawData === false) {
                throw new JsonException();
            }
        }

        $array = [];

        foreach ($relationalColumns as $relationalColumn) {
           $column = [];

            if (isset($rawData[$relationalColumn->id][WorkflowStep::DATA_KEY_TYPE]) &&
                array_key_exists($rawData[$relationalColumn->id][WorkflowStep::DATA_KEY_TYPE], WorkflowStep::DATA_TYPES)) {
                $column[WorkflowStep::DATA_KEY_TYPE] = $rawData[$relationalColumn->id][WorkflowStep::DATA_KEY_TYPE];
            } else {
                $column[WorkflowStep::DATA_KEY_TYPE] = WorkflowStep::DATA_TYPE_INPUT;
            }

            if ($column[WorkflowStep::DATA_KEY_TYPE] === WorkflowStep::DATA_TYPE_FILL_ID) {
                if (isset($rawData[$relationalColumn->id][WorkflowStep::DATA_KEY_FILL_IN])) {
                    $column[WorkflowStep::DATA_KEY_FILL_IN] = $rawData[$relationalColumn->id][WorkflowStep::DATA_KEY_FILL_IN];
                } else {
                    $column[WorkflowStep::DATA_KEY_TYPE] = WorkflowStep::DATA_TYPE_EXCLUDE;
                }
            }

            $array[$relationalColumn->id] = $column;
        }

        return $array;
    }

    /**
     * @param array<TextualColumn> $textualColumns
     * @throws JsonException
     */
    public static function readTextualColumnData(array $textualColumns, string $data = null): array
    {
        if ($data === null) {
            $rawData = [];
        } else {
            $rawData = json_decode($data, true);

            if ($rawData === false) {
                throw new JsonException();
            }
        }

        $array = [];

        foreach ($textualColumns as $textualColumn) {
            $column = [];

            if (isset($rawData[$textualColumn->id][WorkflowStep::DATA_KEY_TYPE]) &&
                array_key_exists($rawData[$textualColumn->id][WorkflowStep::DATA_KEY_TYPE], WorkflowStep::DATA_TYPES)) {
                $column[WorkflowStep::DATA_KEY_TYPE] = $rawData[$textualColumn->id][WorkflowStep::DATA_KEY_TYPE];
            } else {
                $column[WorkflowStep::DATA_KEY_TYPE] = WorkflowStep::DATA_TYPE_INPUT;
            }

            if ($column[WorkflowStep::DATA_KEY_TYPE] === WorkflowStep::DATA_TYPE_DEPENDING) {
                if (isset($rawData[$textualColumn->id][WorkflowStep::DATA_KEY_DEPENDING_FIELD]) &&
                    array_key_exists($rawData[$textualColumn->id][WorkflowStep::DATA_KEY_DEPENDING_FIELD], $textualColumns) &&
                    isset($rawData[$textualColumn->id][WorkflowStep::DATA_KEY_DEPENDING_VALUE])) {
                    $column[WorkflowStep::DATA_KEY_DEPENDING_FIELD] = $rawData[$textualColumn->id][WorkflowStep::DATA_KEY_DEPENDING_FIELD];
                    $column[WorkflowStep::DATA_KEY_DEPENDING_VALUE] = $rawData[$textualColumn->id][WorkflowStep::DATA_KEY_DEPENDING_VALUE];
                } else {
                    $column[WorkflowStep::DATA_KEY_TYPE] = WorkflowStep::DATA_TYPE_EXCLUDE;
                }
            }

            if ($column[WorkflowStep::DATA_KEY_TYPE] === WorkflowStep::DATA_TYPE_STATIC) {
                if (isset($rawData[$textualColumn->id][WorkflowStep::DATA_KEY_STATIC_VALUE])) {
                    $column[WorkflowStep::DATA_KEY_STATIC_VALUE] = $rawData[$textualColumn->id][WorkflowStep::DATA_KEY_STATIC_VALUE];
                } else {
                    $column[WorkflowStep::DATA_KEY_TYPE] = WorkflowStep::DATA_TYPE_EXCLUDE;
                }
            }

            $array[$textualColumn->id] = $column;
        }

        return $array;
    }

    /**
     * @param array<RelationalColumn> $relationalColumns
     * @param array<string, mixed> $formData
     * @return string|null
     * @throws JsonException
     */
    public static function writeRelationalColumnData(array $relationalColumns, array $formData)
    {
        $array = [];

        foreach ($relationalColumns as $column) {
            $typeKey = "rel-".$column->id."-type";

            if (isset($formData[$typeKey]) && $formData[$typeKey] === WorkflowStep::DATA_TYPE_FILL_ID) {
                $array[$column->id][WorkflowStep::DATA_KEY_TYPE] = WorkflowStep::DATA_TYPE_FILL_ID;
                $array[$column->id][WorkflowStep::DATA_KEY_FILL_IN] = $formData["rel-".$column->id."-fill-in"];
            }

            if (isset($formData[$typeKey]) && $formData[$typeKey] === WorkflowStep::DATA_TYPE_EXCLUDE) {
                $array[$column->id][WorkflowStep::DATA_KEY_TYPE] = WorkflowStep::DATA_TYPE_EXCLUDE;
            }
        }

        if (count($array) === 0) {
            return null;
        }

        $data = json_encode($array);

        if ($data === false) {
            throw new JsonException();
        }

        return $data;
    }

    /**
     * @param array<TextualColumn> $textualColumns
     * @param array<string, mixed> $formData
     * @throws JsonException
     */
    public static function writeTextualColumnData(array $textualColumns, array $formData)
    {
        $array = [];

        foreach ($textualColumns as $column) {
            $typeKey = "txt-".$column->id."-type";

            if (isset($formData[$typeKey]) && $formData[$typeKey] === WorkflowStep::DATA_TYPE_DEPENDING) {
                $array[$column->id][WorkflowStep::DATA_KEY_TYPE] = WorkflowStep::DATA_TYPE_DEPENDING;
                $array[$column->id][WorkflowStep::DATA_KEY_DEPENDING_FIELD] = $formData["txt-".$column->id."-depending-field"];
                $array[$column->id][WorkflowStep::DATA_KEY_DEPENDING_VALUE] = $formData["txt-".$column->id."-depending-value"];
            }

            if (isset($formData[$typeKey]) && $formData[$typeKey] === WorkflowStep::DATA_TYPE_EXCLUDE) {
                $array[$column->id][WorkflowStep::DATA_KEY_TYPE] = WorkflowStep::DATA_TYPE_EXCLUDE;
            }

            if (isset($formData[$typeKey]) && $formData[$typeKey] === WorkflowStep::DATA_TYPE_STATIC) {
                $array[$column->id][WorkflowStep::DATA_KEY_TYPE] = WorkflowStep::DATA_TYPE_STATIC;
                $array[$column->id][WorkflowStep::DATA_KEY_STATIC_VALUE] = $formData["txt-".$column->id."-static-value"];
            }
        }

        if (count($array) === 0) {
            return null;
        }

        $data = json_encode($array);

        if ($data === false) {
            throw new JsonException();
        }

        return $data;
    }

    /** @var string */
    public $id;

    /** @var string */
    public $tableId;

    /** @var string */
    public $tableLabel;

    /** @var string|null */
    public $tableDescription;

    /** @var string|null */
    public $label;

    /** @var string|null */
    public $description;

    /** @var string */
    public $position;

    /** @var string|null */
    public $relationalColumnData;

    /** @var string|null */
    public $textualColumnData;

    /**
     * @param array<string> $data
     */
    public function __construct(array $data)
    {
        $this->id = $data["id"];
        $this->tableId = $data["table_id"];
        $this->tableLabel = $data["table_label"];
        $this->label = $data["label"];
        $this->description = $data["description"];
        $this->position = $data["position"];
        $this->relationalColumnData = $data["relcoldata"];
        $this->textualColumnData = $data["txtcoldata"];

        if (isset($data["table_description"])) {
            $this->tableDescription = $data["table_description"];
        }
    }

    public function delete(Workflow $workflow, string $userId)
    {
        MySQLConnection::$instance->execute("DELETE FROM `dbc_workflow_step` WHERE `id`=?", [$this->id]);
        $workflow->setUpdated($userId);
    }

    public function edit(Workflow $workflow, string $userId, string $label = null, string $description = null, string $relationalColumnData = null, string $textualColumnData = null)
    {
        MySQLConnection::$instance->execute("UPDATE `dbc_workflow_step` SET `label`=?, `description`=?, `relcoldata`=?, `txtcoldata`=? WHERE `id`=?", [$label, $description, $relationalColumnData, $textualColumnData, $this->id]);
        $workflow->setUpdated($userId);
        $this->label = $label;
        $this->description = $description;
        $this->relationalColumnData = $relationalColumnData;
        $this->textualColumnData = $textualColumnData;
    }

    public function getLabel(): string
    {
        return $this->label === null ? "Schritt ".$this->position : "Schritt ".$this->position.": ".$this->label;
    }
}
