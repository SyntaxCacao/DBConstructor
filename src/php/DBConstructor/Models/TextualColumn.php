<?php

declare(strict_types=1);

namespace DBConstructor\Models;

use DBConstructor\SQL\MySQLConnection;
use DBConstructor\Validation\Validator;
use Exception;

class TextualColumn extends Column
{
    const TYPE_BOOLEAN = "boolean";

    const TYPE_DATE = "date";

    const TYPE_DOUBLE = "double";

    const TYPE_ENUM = "enum";

    const TYPE_INTEGER = "integer";

    const TYPE_SET = "set";

    const TYPE_TEXT = "text";

    const TYPES = [
        TextualColumn::TYPE_TEXT => "Text",
        TextualColumn::TYPE_INTEGER => "Ganze Zahl",
        TextualColumn::TYPE_DATE => "Datum"
    ];

    public static function create(string $tableId, string $name, string $label, string $position, string $type, string $description = null, string $rules = null): string
    {
        MySQLConnection::$instance->execute("UPDATE `dbc_column_relational` SET `position`=`position`+1 WHERE `table_id`=? AND `position`>=?", [$tableId, $position]);

        MySQLConnection::$instance->execute("INSERT INTO `dbc_column_textual` (`table_id`, `name`, `label`, `description`, `position`, `type`, `rules`) VALUES (?, ?, ?, ?, ?, ?, ?)", [$tableId, $name, $label, $description, $position, $type, $rules]);

        return MySQLConnection::$instance->getLastInsertId();
    }

    public static function load($id)
    {
        MySQLConnection::$instance->execute("SELECT * FROM `dbc_column_textual` WHERE `id`=?", [$id]);
        $result = MySQLConnection::$instance->getSelectedRows();

        if (count($result) != 1) {
            return null;
        }

        return new TextualColumn($result[0]);
    }

    /**
     * @return TextualColumn[]
     */
    public static function loadList(string $tableId): array
    {
        MySQLConnection::$instance->execute("SELECT * FROM `dbc_column_textual` WHERE `table_id`=? ORDER BY `position`", [$tableId]);
        $result = MySQLConnection::$instance->getSelectedRows();
        $list = [];

        foreach ($result as $row) {
            $list[] = new TextualColumn($row);
        }

        return $list;
    }

    /** @var string */
    public $type;

    /**
     * @param string[] $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
        $this->type = $data["type"];
    }

    public function delete()
    {
        TextualField::delete($this->id);
        MySQLConnection::$instance->execute("DELETE FROM `dbc_column_textual` WHERE `id`=?", [$this->id]);
    }

    public function edit(string $name, string $label, string $description = null)
    {
        MySQLConnection::$instance->execute("UPDATE `dbc_column_textual` SET `name`=?, `label`=?, `description`=? WHERE `id`=?", [$name, $label, $description, $this->id]);
        $this->name = $name;
        $this->label = $label;
        $this->description = $description;
    }

    public function editRules(string $type, string $rules = null)
    {
        MySQLConnection::$instance->execute("UPDATE `dbc_column_textual` SET `type`=?, `rules`=? WHERE `id`=?", [$type, $rules, $this->id]);
        $this->type = $type;
        $this->rules = $rules;
    }

    public function getTypeLabel(): string
    {
        return TextualColumn::TYPES[$this->type];
    }

    /**
     * @throws Exception
     */
    public function getValidator(): Validator
    {
        return Validator::fromJSON($this->rules, $this->type);
    }

    public function move(int $newPosition)
    {
        parent::move_internal("dbc_column_textual", $newPosition);
    }
}
