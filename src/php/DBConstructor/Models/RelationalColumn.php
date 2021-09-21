<?php

declare(strict_types=1);

namespace DBConstructor\Models;

use DBConstructor\SQL\MySQLConnection;
use DBConstructor\Validation\NotNullRule;
use DBConstructor\Validation\Validator;
use Exception;

class RelationalColumn
{
    public static function create(string $tableId, string $targetTableId, /*$labelColumnId, */ string $name, string $label, string $description = null, string $rules = null): string
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

    public static function load(string $id)
    {
        MySQLConnection::$instance->execute("SELECT c.*, t.`name` AS `target_table_name`, t.`label` AS `target_table_label` FROM `dbc_column_relational` c LEFT JOIN `dbc_table` t ON c.`target_table_id` = t.`id` WHERE c.`id`=?", [$id]);
        $result = MySQLConnection::$instance->getSelectedRows();

        if (count($result) != 1) {
            return null;
        }

        return new RelationalColumn($result[0]);
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

    public function delete()
    {
        RelationalField::delete($this->id);
        MySQLConnection::$instance->execute("DELETE FROM `dbc_column_relational` WHERE `id`=?", [$this->id]);
    }

    public function edit(string $targetTableId, string $name, string $label, string $description = null, string $rules = null)
    {
        MySQLConnection::$instance->execute("UPDATE `dbc_column_relational` SET `target_table_id`=?, `name`=?, `label`=?, `description`=?, `rules`=? WHERE `id`=?", [$targetTableId, $name, $label, $description, $rules, $this->id]);
        $this->targetTableId = $targetTableId;
        $this->name = $name;
        $this->label = $label;
        $this->description = $description;
        $this->rules = $rules;
    }

    /**
     * @throws Exception
     */
    public function getValidator(): Validator
    {
        return Validator::fromJSON($this->rules);
    }

    /**
     * @throws Exception
     */
    public function isOptional(): bool
    {
        // TODO: Do this in a totally different way
        $validator = $this->getValidator();

        foreach ($validator->rules as $rule) {
            if ($rule instanceof NotNullRule) {
                return ! $rule->ruleValue;
            }
        }

        return true;
    }

    public function invalidateFields()
    {

    }
}
