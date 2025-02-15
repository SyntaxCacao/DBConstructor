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

    /* TODO */
    const TYPES_EN = [
        TextualColumn::TYPE_TEXT => "Text",
        TextualColumn::TYPE_SELECTION => "Selection",
        TextualColumn::TYPE_DATE => "Date",
        TextualColumn::TYPE_INTEGER => "Integer",
        TextualColumn::TYPE_DECIMAL => "Decimal",
        TextualColumn::TYPE_BOOLEAN => "Boolean"
    ];

    public static function create(string $tableId, string $name, string $label, string $instructions = null, string $position, string $type, Type $validationType, bool $hide): string
    {
        MySQLConnection::$instance->execute("UPDATE `dbc_column_textual` SET `position`=`position`+1 WHERE `table_id`=? AND `position`>=?", [$tableId, $position]);

        MySQLConnection::$instance->execute("INSERT INTO `dbc_column_textual` (`table_id`, `name`, `label`, `instructions`, `position`, `type`, `rules`, `hide`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)", [$tableId, $name, $label, $instructions, $position, $type, $validationType->toJson(), intval($hide)]);

        return MySQLConnection::$instance->getLastInsertId();
    }

    /**
     * For {@code SelectionType} with {@code allowMultiple === true}.
     *
     * @return array|null
     */
    public static function decodeOptions(string $json = null)
    {
        if ($json === null) {
            return null;
        }

        $array = json_decode($json);

        if ($array === null) {
            throw new JsonException();
        }

        if (empty($array)) {
            return null;
        }

        return $array;
    }

    public static function deleteAll(string $tableId)
    {
        MySQLConnection::$instance->execute("DELETE FROM `dbc_column_textual` WHERE `table_id`=?", [$tableId]);
    }

    /**
     * For {@code SelectionType} with {@code allowMultiple === true}.
     *
     * @return string|null
     */
    public static function encodeOptions(array $array = null)
    {
        if (empty($array)) {
            return null;
        }

        $string = json_encode($array);

        if ($string === false) {
            throw new JsonException();
        }

        return $string;
    }

    /**
     * Returns {@code true} if the two given sets of options are equivalent, {@code false} otherwise.
     */
    public static function isEquivalent(array $options = null, array $moreOptions = null): bool
    {
        return ! (($options === null xor $moreOptions === null) || ($options !== null &&
                (count(array_diff($options, $moreOptions)) > 0 ||
                    count(array_diff($moreOptions, $options))) > 0));
    }

    /**
     * @return TextualColumn|null
     */
    public static function load(string $id)
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
        TextualField::deleteColumn($this->id);
        MySQLConnection::$instance->execute("DELETE FROM `dbc_column_textual` WHERE `id`=?", [$this->id]);
        MySQLConnection::$instance->execute("UPDATE `dbc_column_textual` SET `position`=`position`-1 WHERE `table_id`=? AND `position`>=?", [$this->tableId, $this->position]);
        Row::revalidateAllInvalid($this->tableId);
    }

    public function edit(string $name, string $label, string $instructions = null, string $type, Type $validationType, bool $hide)
    {
        $rules = $validationType->toJson();

        MySQLConnection::$instance->execute("UPDATE `dbc_column_textual` SET `name`=?, `label`=?, `instructions`=?, `type`=?, `rules`=?, `hide`=? WHERE `id`=?", [$name, $label, $instructions, $type, $rules, intval($hide), $this->id]);

        $this->name = $name;
        $this->label = $label;
        $this->instructions = $instructions;
        $this->type = $type;
        $this->rules = $rules;
        $this->validationType = $validationType;
        $this->hide = $hide;
    }

    /**
     * @param TextualField $field field->value must not be null
     */
    public function generateCellValue(TextualField $field = null): string
    {
        if ($field === null) {
            $value = "Zelle fehlt";
        } else {
            $value = $this->generatePrintableValue($field->value);
        }

        $html = '<td class="table-cell';
        $valueEscaped = false;

        if ($field === null) {
            $html .= ' table-cell-invalid table-cell-null';
        } else {
            if (! $field->valid) {
                $html .= ' table-cell-invalid';
            }

            if ($field->value === null) {
                $html .= ' table-cell-null';
            } else if ($this->type === TextualColumn::TYPE_DATE) {
                $html .= ' table-cell-tabular';
            } else if ($this->type === TextualColumn::TYPE_DECIMAL) {
                if (preg_match("/^(0|-?[1-9]+[0-9]*)(?:\.([0-9]*[1-9]+))?$/", $value, $matches)) {
                    $html .= ' table-cell-numeric';
                    $valueEscaped = true;

                    $value = number_format((int) $matches[1], 0, ".", '<span class="table-cell-numeric-thsp">&thinsp;</span>');

                    if (substr($value, 0, 1) === "-") {
                        $html .= ' table-cell-numeric-negative';
                        // replacing hyphen character with actual minus sign (U+2212) for presentation
                        $value = "−".ltrim($value, "-");
                    }

                    /** @var DecimalType $validationType */
                    $validationType = $this->getValidationType();
                    $valueDecimals = 0;

                    if (isset($matches[2])) {
                        $valueDecimals = strlen($matches[2]);
                        $value .= ".".$valueDecimals;
                    }

                    if ($valueDecimals < $validationType->decimalDigits) {
                        if ($valueDecimals === 0) {
                            $value .= ".";
                        }

                        $value .= str_repeat("0", ($validationType->decimalDigits - $valueDecimals));
                    }
                }
            } else if ($this->type === TextualColumn::TYPE_INTEGER) {
                if (ctype_digit(ltrim($value, "-"))) {
                    $html .= ' table-cell-numeric';
                    $valueEscaped = true;

                    if ((int) $value < 0) {
                        $html .= ' table-cell-numeric-negative';
                        // replacing simple hyphen character with actual minus sign (U+2212) for presentation
                        $value = "−".number_format((int) ltrim($value, "-"), 0, ".", '<span class="table-cell-numeric-thsp">&thinsp;</span>');
                    } else {
                        $value = number_format((int) $value, 0, ".", '<span class="table-cell-numeric-thsp">&thinsp;</span>');
                    }
                }
            }
        }

        return $html.'">'.($valueEscaped ? $value : htmlentities($value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5)).'</td>';
    }

    public function generateIndicator(Validator $validator, bool $success): string
    {
        $html = '<div class="js-result" data-result="'.($success ? "1" : "0").'"></div>';

        foreach ($validator->rules as $rule) {
            if ($rule->result == Rule::RESULT_VALID) {
                $html .= '<div class="validation-step"><div class="validation-step-icon"><span class="bi bi-check"></span></div><p class="validation-step-description">'.$rule->description.'</p></div>';
            } else if ($rule->result == Rule::RESULT_INVALID) {
                $html .= '<div class="validation-step"><div class="validation-step-icon"><span class="bi bi-x"></span></div><p class="validation-step-description">'.$rule->description.'</p></div>';
            } else {
                $html .= '<div class="validation-step"><div class="validation-step-icon"><span class="bi bi-dash"></span></div><p class="validation-step-description">'.$rule->description.'</p></div>';
            }
        }

        return $html;
    }

    public function generateInput(Field $field, array $errorMessages, bool $edit = false)
    {
        $type = $this->getValidationType();

        if ($type instanceof SelectionType && $type->allowMultiple) {
            $insertLabel = "Eingabe · Mehrfachauswahl";
        } else {
            $insertLabel = "Eingabe · ".$this->getTypeLabel();
        }

        $validator = $type->buildValidator();
        $valid = $validator->validate($field->value);

        parent::generateInput_internal($field, $errorMessages, $edit, $valid, $this->generateIndicator($validator, $valid), true, $insertLabel, 'data-column-id="'.htmlentities($this->id).'"');
    }

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

            if ($type->allowMultiple) {
                try {
                    $options = self::decodeOptions($value);
                } catch (JsonException $exception) {
                    $options = null;
                }

                if ($options !== null) {
                    $labels = [];

                    foreach ($options as $option) {
                        if (array_key_exists($option, $type->options)) {
                            $labels[] = $type->options[$option];
                        } else {
                            $labels[] = $option;
                        }
                    }

                    $value = implode(", ", $labels);
                }
            } else {
                if (array_key_exists($value, $type->options)) {
                    $value = $type->options[$value];
                }
            }
        }

        $value = str_replace("\n", " ", $value);

        if (strlen($value) > 40) {
            return substr($value, 0, 40)."...";
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

    public function revalidate()
    {
        // TODO Don't load everything at once
        $fields = TextualField::loadColumn($this->id);
        $type = $this->getValidationType();
        $validator = $type->buildValidator();

        foreach ($fields as $field) {
            $value = $field->value;

            if ($type instanceof SelectionType && $type->allowMultiple) {
                $value = TextualColumn::decodeOptions($value);
            }

            $valid = $validator->validate($value);

            if ($field->valid !== $valid) {
                $field->setValid($valid);
            }
        }
    }
}
