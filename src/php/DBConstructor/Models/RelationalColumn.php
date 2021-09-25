<?php

declare(strict_types=1);

namespace DBConstructor\Models;

use DBConstructor\SQL\MySQLConnection;
use DBConstructor\Validation\NotNullRule;
use DBConstructor\Validation\Validator;
use Exception;

class RelationalColumn extends Column
{
    public static function create(string $tableId, string $targetTableId, /*$labelColumnId, */ string $name, string $label, string $position, string $description = null, string $rules = null): string
    {
        MySQLConnection::$instance->execute("UPDATE `dbc_column_relational` SET `position`=`position`+1 WHERE `table_id`=? AND `position`>=?", [$tableId, $position]);

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
    public $targetTableId;

    /** @var string */
    public $targetTableName;

    /** @var string */
    public $targetTableLabel;

    /** @var string */
    public $labelColumnId;

    public function __construct(array $data)
    {
        parent::__construct($data);
        $this->targetTableId = $data["target_table_id"];
        $this->targetTableName = $data["target_table_name"];
        $this->targetTableLabel = $data["target_table_label"];
        $this->labelColumnId = $data["label_column_id"];
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

    public function move(int $newPosition)
    {
        parent::move_internal("dbc_column_relational", $newPosition);
    }
}
