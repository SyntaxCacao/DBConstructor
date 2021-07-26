<?php

declare(strict_types=1);

namespace DBConstructor\Models;

use DBConstructor\SQL\MySQLConnection;
use DBConstructor\Validation\Validator;
use Exception;

class RelationalColumn
{
    /**
     * @param string|null $description
     * @param string|null $rules
     */
    public static function create(string $tableId, string $targetTableId, /*$labelColumnId, */ string $name, string $label, $description, $rules): string
    {
        MySQLConnection::$instance->execute("SELECT `position` FROM `dbc_column_relational` WHERE `table_id`=? ORDER BY `position` DESC LIMIT 1", [$tableId]);

        $result = MySQLConnection::$instance->getSelectedRows();
        $position = 1;

        if (count($result) > 0) {
            $position = intval($result[0]["position"]) + 1;
        }

        MySQLConnection::$instance->execute("INSERT INTO `dbc_column_relational` (`table_id`, `target_table_id`, /*`label_column_id`, */`name`, `label`, `description`, `position`, `rules`) VALUES (?, ?, /*?, */?, ?, ?, ?, ?)", [$tableId, $targetTableId, /*$labelColumnId, */ $name, $label, $description, $position, $rules]);

        return MySQLConnection::$instance->getLastInsertId();
    }

    public static function isNameAvailable(string $tableId, string $name): bool
    {
        return TextualColumn::isNameAvailable($tableId, $name);
    }

    public static function loadList(string $tableId): array
    {
        MySQLConnection::$instance->execute("SELECT c.*, t.`name` AS `target_table_name`, t.`label` AS `target_table_label` FROM `dbc_column_relational` c LEFT JOIN `dbc_table` t ON c.`target_table_id` = t.`id` WHERE c.`table_id`=? ORDER BY c.`position`", [$tableId]);
        $result = MySQLConnection::$instance->getSelectedRows();
        $list = [];

        foreach ($result as $row) {
            $list[] = new RelationalColumn($row);
        }

        return $list;
    }

    /** @var string */
    public $id;

    /** @var string */
    public $tableId;

    /** @var string */
    public $targetTableId;

    /** @var string */
    public $targetTableName;

    /** @var string */
    public $targetTableLabel;

    /** @var string */
    public $labelColumnId;

    /** @var string */
    public $name;

    /** @var string */
    public $label;

    /** @var string|null */
    public $description;

    /** @var string */
    public $position;

    /** @var string|null */
    public $rules;

    /** @var string */
    public $created;

    public function __construct(array $data)
    {
        $this->id = $data["id"];
        $this->tableId = $data["table_id"];
        $this->targetTableId = $data["target_table_id"];
        $this->targetTableName = $data["target_table_name"];
        $this->targetTableLabel = $data["target_table_label"];
        $this->labelColumnId = $data["label_column_id"];
        $this->name = $data["name"];
        $this->label = $data["label"];
        $this->description = $data["description"];
        $this->position = $data["position"];
        $this->rules = $data["rules"];
        $this->created = $data["created"];
    }

    /**
     * @throws Exception
     */
    public function getValidator(): Validator
    {
        return Validator::fromJSON($this->rules);
    }
}
