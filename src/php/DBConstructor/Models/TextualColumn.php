<?php

declare(strict_types=1);

namespace DBConstructor\Models;

use DBConstructor\SQL\MySQLConnection;
use DBConstructor\Util\JsonException;
use DBConstructor\Validation\Types\BooleanType;
use DBConstructor\Validation\Types\DateType;
use DBConstructor\Validation\Types\IntegerType;
use DBConstructor\Validation\Types\SelectionType;
use DBConstructor\Validation\Types\TextType;
use DBConstructor\Validation\Types\Type;
use Exception;

class TextualColumn extends Column
{
    const TYPE_BOOLEAN = "bool";

    const TYPE_DATE = "date";

    const TYPE_INTEGER = "int";

    const TYPE_SELECTION = "select";

    const TYPE_TEXT = "text";

    const TYPES = [
        TextualColumn::TYPE_TEXT => "Text",
        TextualColumn::TYPE_SELECTION => "Auswahl",
        TextualColumn::TYPE_DATE => "Datum",
        TextualColumn::TYPE_INTEGER => "Ganze Zahl",
        TextualColumn::TYPE_BOOLEAN => "Boolsches Feld"
    ];

    /**
     * @throws JsonException
     */
    public static function create(string $tableId, string $name, string $label, string $description = null, string $position, string $type, Type $validationType): string
    {
        MySQLConnection::$instance->execute("UPDATE `dbc_column_textual` SET `position`=`position`+1 WHERE `table_id`=? AND `position`>=?", [$tableId, $position]);

        MySQLConnection::$instance->execute("INSERT INTO `dbc_column_textual` (`table_id`, `name`, `label`, `description`, `position`, `type`, `rules`) VALUES (?, ?, ?, ?, ?, ?, ?)", [$tableId, $name, $label, $description, $position, $type, $validationType->toJson()]);

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

    /** @var string|null */
    public $rules;

    /** **/

    /** @var Type|null */
    public $validationType;

    /**
     * @param string[] $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
        $this->type = $data["type"];
        $this->rules = $data["rules"];
    }

    public function delete()
    {
        TextualField::delete($this->id);
        MySQLConnection::$instance->execute("DELETE FROM `dbc_column_textual` WHERE `id`=?", [$this->id]);
        MySQLConnection::$instance->execute("UPDATE `dbc_column_textual` SET `position`=`position`-1 WHERE `table_id`=? AND `position`>=?", [$this->tableId, $this->position]);
    }

    /**
     * @throws JsonException
     */
    public function edit(string $name, string $label, string $description = null, string $type, Type $validationType)
    {
        $rules = $validationType->toJson();

        MySQLConnection::$instance->execute("UPDATE `dbc_column_textual` SET `name`=?, `label`=?, `description`=?, `type`=?, `rules`=? WHERE `id`=?", [$name, $label, $description, $type, $rules, $this->id]);

        $this->name = $name;
        $this->label = $label;
        $this->description = $description;
        $this->type = $type;
        $this->rules = $rules;
        $this->validationType = $validationType;
    }

    public function getTypeLabel(): string
    {
        return TextualColumn::TYPES[$this->type];
    }

    /**
     * @throws Exception
     * @throws JsonException
     */
    public function getValidationType(): Type
    {
        if (! is_null($this->validationType)) {
            return $this->validationType;
        }

        switch ($this->type) {
            case TextualColumn::TYPE_TEXT:
                $type = new TextType();
                break;
            case TextualColumn::TYPE_SELECTION:
                $type = new SelectionType();
                break;
            case TextualColumn::TYPE_DATE:
                $type = new DateType();
                break;
            case TextualColumn::TYPE_INTEGER:
                $type = new IntegerType();
                break;
            case TextualColumn::TYPE_BOOLEAN:
                $type = new BooleanType();
                break;
            default:
                throw new Exception("Unknown type: ".$this->type);
        }

        $this->validationType = $type;
        $this->validationType->fromJson($this->rules);
        return $this->validationType;
    }

    public function move(int $newPosition)
    {
        parent::move_internal("dbc_column_textual", $newPosition);
    }
}
