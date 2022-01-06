<?php

declare(strict_types=1);

namespace DBConstructor\Models;

use DBConstructor\Forms\Fields\Field;
use DBConstructor\SQL\MySQLConnection;
use DBConstructor\Util\JsonException;
use DBConstructor\Validation\Rules\Rule;
use DBConstructor\Validation\Types\BooleanType;
use DBConstructor\Validation\Types\DateType;
use DBConstructor\Validation\Types\DecimalType;
use DBConstructor\Validation\Types\IntegerType;
use DBConstructor\Validation\Types\SelectionType;
use DBConstructor\Validation\Types\TextType;
use DBConstructor\Validation\Types\Type;
use DBConstructor\Validation\Validator;
use Exception;

class TextualColumn extends Column
{
    const TYPE_BOOLEAN = "bool";

    const TYPE_DATE = "date";

    const TYPE_DECIMAL = "dec";

    const TYPE_INTEGER = "int";

    const TYPE_SELECTION = "select";

    const TYPE_TEXT = "text";

    const TYPES = [
        TextualColumn::TYPE_TEXT => "Text",
        TextualColumn::TYPE_SELECTION => "Auswahl",
        TextualColumn::TYPE_DATE => "Datum",
        TextualColumn::TYPE_INTEGER => "Ganze Zahl",
        TextualColumn::TYPE_DECIMAL => "Dezimalzahl",
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

    /**
     * @return TextualColumn|null
     */
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
     * @return array<string, TextualColumn>
     */
    public static function loadList(string $tableId): array
    {
        MySQLConnection::$instance->execute("SELECT * FROM `dbc_column_textual` WHERE `table_id`=? ORDER BY `position`", [$tableId]);
        $result = MySQLConnection::$instance->getSelectedRows();
        $list = [];

        foreach ($result as $row) {
            $obj = new TextualColumn($row);
            $list[$obj->id] = $obj;
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
     * @param array<string, string> $data
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
        Row::revalidateAllInvalid($this->tableId);
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

    /**
     * @param TextualField $field field->value must not be null
     * @throws JsonException
     */
    public function generateCellValue(TextualField $field = null): string
    {
        if ($field === null) {
            $value = "Zelle fehlt";
        } else {
            $value = $this->generatePrintableValue($field->value);
        }

        $html = '<td class="table-cell';

        if ($field === null) {
            $html .= ' table-cell-invalid table-cell-null';
        } else {
            if (! $field->valid) {
                $html .= ' table-cell-invalid';
            }

            if ($field->value === null) {
                $html .= ' table-cell-null';
            } else if ($this->type === TextualColumn::TYPE_DECIMAL) {
                //if (is_numeric($value)) {
                $matches = [];
                if (preg_match("/^(0|-?[1-9]+[0-9]*)(?:\.([0-9]*[1-9]+))?$/", $value, $matches)) {
                    $html .= ' table-cell-numeric';

                    if (substr($value, 0, 1) === "-") {
                        $html .= ' table-cell-numeric-negative';
                    }

                    /** @var DecimalType $validationType */
                    $validationType = $this->getValidationType();
                    $valueDecimals = 0;

                    if (isset($matches[2])) {
                        $valueDecimals = strlen($matches[2]);
                    }

                    if ($valueDecimals < $validationType->decimalDigits) {
                        if ($valueDecimals === 0) {
                            $value .= ".";
                        }

                        $value .= str_repeat("0", ($validationType->decimalDigits - $valueDecimals));
                    }
                }
            } else if (ctype_digit(ltrim($value, "-"))) {
                $html .= ' table-cell-numeric';

                if (intval($value) < 0) {
                    $html .= ' table-cell-numeric-negative';
                }
            }
        }

        return $html.'">'.htmlentities($value).'</td>';
    }

    public function generateIndicator(Validator $validator, bool $success): string
    {
        $html = '<div class="js-result" data-result="'.($success ? "1" : "0").'"></div>';

        foreach ($validator->rules as $rule) {
            if ($rule->result == Rule::RESULT_VALID) {
                $html .= '<div class="validation-step"><div class="validation-step-icon"><span class="bi bi-check-lg"></span></div><p class="validation-step-description">'.$rule->description.'</p></div>';
            } else if ($rule->result == Rule::RESULT_INVALID) {
                $html .= '<div class="validation-step"><div class="validation-step-icon"><span class="bi bi-x-lg"></span></div><p class="validation-step-description">'.$rule->description.'</p></div>';
            } else {
                $html .= '<div class="validation-step"><div class="validation-step-icon"><span class="bi bi-dash-lg"></span></div><p class="validation-step-description">'.$rule->description.'</p></div>';
            }
        }

        return $html;
    }

    /**
     * @throws JsonException
     */
    public function generateInput(Field $field, bool $edit = false)
    {
        $validator = $this->getValidationType()->buildValidator();
        $valid = $validator->validate($field->value);

        parent::generateInput_internal($field, $edit, $valid, $this->generateIndicator($validator, $valid), true, "Eingabe · ".$this->getTypeLabel(), 'data-column-id="'.htmlentities($this->id).'"');
    }

    /**
     * @throws JsonException
     */
    public function generatePrintableValue(string $value = null)
    {
        if ($value === null) {
            return "NULL";
        } else if ($this->type === TextualColumn::TYPE_BOOLEAN) {
            /** @var BooleanType $type */
            $type = $this->getValidationType();

            if ($value === BooleanType::VALUE_TRUE) {
                if ($type->trueLabel !== null) {
                    return $type->trueLabel;
                } else {
                    return "true";
                }
            } else if ($value === BooleanType::VALUE_FALSE) {
                if ($type->falseLabel !== null) {
                    return $type->falseLabel;
                } else {
                    return "false";
                }
            }
        } else if ($this->type === TextualColumn::TYPE_DATE) {
            $time = strtotime($value);

            if ($time !== false) {
                return date("d.m.Y", $time);
            }
        } else if ($this->type === TextualColumn::TYPE_SELECTION) {
            /** @var SelectionType $type */
            $type = $this->getValidationType();

            if (in_array($value, array_keys($type->options))) {
                return $type->options[$value];
            }
        }

        $value = str_replace("\n", " ", $value);

        if (strlen($value) > 30) {
            return substr($value, 0, 30)."...";
        } else {
            return $value;
        }
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
            case TextualColumn::TYPE_DECIMAL:
                $type = new DecimalType();
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

    /**
     * @throws JsonException
     */
    public function revalidate()
    {
        // TODO Don't load everything at once
        $fields = TextualField::loadColumn($this->id);
        $validator = $this->getValidationType()->buildValidator();

        foreach ($fields as $field) {
            $valid = $validator->validate($field->value);

            if ($field->valid !== $valid) {
                $field->setValid($valid);
            }
        }
    }
}
