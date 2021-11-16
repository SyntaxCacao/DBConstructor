<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\Projects\Tables\Structure;

use DBConstructor\Application;
use DBConstructor\Forms\Fields\CheckboxField;
use DBConstructor\Forms\Fields\IntegerField;
use DBConstructor\Forms\Fields\ListField;
use DBConstructor\Forms\Fields\ListFieldColumn;
use DBConstructor\Forms\Fields\SelectField;
use DBConstructor\Forms\Fields\TextField;
use DBConstructor\Forms\Form;
use DBConstructor\Models\TextualColumn;
use DBConstructor\Models\TextualField;
use DBConstructor\Util\JsonException;
use DBConstructor\Validation\Types\BooleanType;
use DBConstructor\Validation\Types\DateType;
use DBConstructor\Validation\Types\DecimalType;
use DBConstructor\Validation\Types\IntegerType;
use DBConstructor\Validation\Types\SelectionType;
use DBConstructor\Validation\Types\TextType;
use Exception;

class TextualColumnForm extends Form
{
    /** @var TextualColumn */
    public $column;

    /** @var string */
    public $projectId;

    /** @var bool */
    public $tableEmpty;

    /** @var string */
    public $tableId;

    public function __construct()
    {
        parent::__construct("textual-column-form");
    }

    /**
     * @param TextualColumn|null $column null on creation
     * @throws JsonException
     */
    public function init(string $projectId, string $tableId, bool $tableEmpty, TextualColumn $column = null)
    {
        $this->projectId = $projectId;
        $this->tableId = $tableId;
        $this->tableEmpty = $tableEmpty;
        $this->column = $column;

        // label
        $this->addField(new ColumnLabelField($column));

        // name
        $this->addField(new ColumnNameField($tableId, $column));

        // type
        $typeFieldName = "type";
        $field = new SelectField($typeFieldName, "Datentyp");
        $field->addOptions(TextualColumn::TYPES);

        if ($column !== null) {
            $field->defaultValue = $column->type;
        }

        $this->addField($field);

        // rules

        // // text //

        if ($column !== null && $column->type == TextualColumn::TYPE_TEXT) {
            /** @var TextType $textType */
            $textType = $column->getValidationType();
        }

        // rule-text-minlength
        $field = new IntegerField("rule-text-minlength", "Mindestlänge");
        $field->dependsOn = $typeFieldName;
        $field->dependsOnValue = TextualColumn::TYPE_TEXT;
        $field->minValue = 1;
        $field->required = false;

        if (isset($textType)) {
            $field->defaultValue = $textType->minLength;
        }

        $this->addField($field);

        // rule-text-maxlength
        $field = new IntegerField("rule-text-maxlength", "Maximallänge");
        $field->dependsOn = $typeFieldName;
        $field->dependsOnValue = TextualColumn::TYPE_TEXT;
        $field->description = "Wird neben der Validierung auch zur Bestimmung des passenden SQL-Datentyps benötigt";
        $field->minValue = 0;

        if (isset($textType)) {
            $field->defaultValue = $textType->maxLength;
        }

        $this->addField($field);

        // rule-text-regex
        $field = new RegExField("rule-text-regex", $typeFieldName, TextualColumn::TYPE_TEXT);

        if (isset($textType)) {
            $field->defaultValue = $textType->regEx;
        }

        $this->addField($field);

        // rule-text-fieldtype
        $field = new SelectField("rule-text-fieldtype", "Eingabefeld");
        $field->dependsOn = $typeFieldName;
        $field->dependsOnValue = TextualColumn::TYPE_TEXT;
        $field->description = "Wirkt sich nicht auf die Validierung aus";
        $field->addOption(TextType::FIELD_INPUT, "Einzeilig");
        $field->addOption(TextType::FIELD_TEXTAREA_SMALL, "Mehrzeilig, geringere Höhe");
        $field->addOption(TextType::FIELD_TEXTAREA_LARGE, "Mehrzeilig, größere Höhe");

        if (isset($textType)) {
            $field->defaultValue = $textType->fieldType;
        }

        $this->addField($field);

        // rule-text-markdown
        $field = new SelectField("rule-text-markdown", "Markdown erlauben");
        $field->dependsOn = $typeFieldName;
        $field->dependsOnValue = TextualColumn::TYPE_TEXT;
        $field->description = "Das Markdown-Eingabefeld ist nur verfügbar, wenn ein mehrzeiliges Eingabefeld gewählt wurde";
        $field->addOption(TextType::MARKDOWN_DISABLED, "Nein");
        $field->addOption(TextType::MARKDOWN_ENABLED_EXPORT_MD, "Ja, unverändert als Markdown exportieren");
        $field->addOption(TextType::MARKDOWN_ENABLED_EXPORT_HTML, "Ja, beim Export in HTML umwandeln (noch nicht wirksam)");

        if (isset($textType)) {
            $field->defaultValue = $textType->markdown;
        }

        $this->addField($field);

        // // select // //

        if ($column !== null && $column->type == TextualColumn::TYPE_SELECTION) {
            /** @var SelectionType $selectType */
            $selectType = $column->getValidationType();
        }

        // rule-select-options
        $field = new ListField("rule-select-options", "Auswahlmöglichkeiten");
        $field->dependsOn = $typeFieldName;
        $field->dependsOnValue = TextualColumn::TYPE_SELECTION;

        $labelColumn = "label";
        $listColumn = new ListFieldColumn($labelColumn, "Bezeichnung");
        $listColumn->maxLength = 30;
        $field->addColumn($listColumn);

        $nameColumn = "name";
        $listColumn = new ListFieldColumn($nameColumn, "Technischer Name");
        $listColumn->maxLength = 30;
        $listColumn->monospace = true;
        $field->addColumn($listColumn);

        if (isset($selectType)) {
            foreach ($selectType->options as $name => $label) {
                $field->addRow([$labelColumn => $label, $nameColumn => $name]);
            }
        }

        $this->addField($field);

        // rule-select-allowmultiple
        $field = new CheckboxField("rule-select-allowmultiple", "Mehrfachauswahl erlauben");
        $field->dependsOn = $typeFieldName;
        $field->dependsOnValue = TextualColumn::TYPE_SELECTION;
        $field->description = "Noch nicht verfügbar";
        $field->disabled = true;

        if (isset($selectType)) {
            $field->defaultValue = $selectType->allowMultiple;
        }

        $this->addField($field);

        // rule-select-separator
        $field = new SelectField("rule-select-separator", "Trennzeichen");
        $field->dependsOn = $typeFieldName;
        $field->dependsOnValue = TextualColumn::TYPE_SELECTION;
        $field->description = "Im Falle der Mehrfachauswahl werden die gewählten Optionen beim Export durch dieses Zeichen getrennt";
        $field->disabled = true;
        $field->addOption(SelectionType::SEPARATOR_SPACE, "Leerzeichen");
        $field->addOption(SelectionType::SEPARATOR_COMMA, "Komma");
        $field->addOption(SelectionType::SEPARATOR_SEMICOLON, "Semikolon");

        if (isset($selectType)) {
            $field->defaultValue = $selectType->separator;
        } else {
            // Disabled selection fields always need a defaultValue
            // TODO Remove when enabling field
            $field->defaultValue = SelectionType::SEPARATOR_SPACE;
        }

        $this->addField($field);

        // // int //

        if ($column !== null && $column->type == TextualColumn::TYPE_INTEGER) {
            /** @var IntegerType $intType */
            $intType = $column->getValidationType();
        }

        // rule-int-mindigits
        $field = new IntegerField("rule-int-mindigits", "Mindeststellenzahl");
        $field->dependsOn = $typeFieldName;
        $field->dependsOnValue = TextualColumn::TYPE_INTEGER;
        $field->description = "Mögliche Vorzeichen werden hier nicht einberechnet";
        $field->maxValue = 19;
        $field->minValue = 1;
        $field->required = false;

        if (isset($intType)) {
            $field->defaultValue = $intType->minDigits;
        }

        $this->addField($field);

        // rule-int-maxdigits
        $field = new IntegerField("rule-int-maxdigits", "Maximalstellenzahl");
        $field->defaultValue = (string) 10;
        $field->dependsOn = $typeFieldName;
        $field->dependsOnValue = TextualColumn::TYPE_INTEGER;
        $field->description = "Wird neben der Validierung auch zur Bestimmung des passenden SQL-Datentyps benötigt";
        $field->maxValue = 19;
        $field->minValue = 1;

        if (isset($intType)) {
            $field->defaultValue = $intType->maxDigits;
        }

        $this->addField($field);

        // rule-int-minvalue
        $field = new IntegerField("rule-int-minvalue", "Mindestwert");
        $field->dependsOn = $typeFieldName;
        $field->dependsOnValue = TextualColumn::TYPE_INTEGER;
        $field->description = "Geben Sie zur Bestimmung des passenden SQL-Datentyps 0 ein, wenn negative Zahlen nicht in Betracht kommen";
        $field->required = false;

        if (isset($intType)) {
            $field->defaultValue = $intType->minValue;
        }

        $this->addField($field);

        // rule-int-maxvalue
        $field = new IntegerField("rule-int-maxvalue", "Maximalwert");
        $field->dependsOn = $typeFieldName;
        $field->dependsOnValue = TextualColumn::TYPE_INTEGER;
        $field->required = false;

        if (isset($intType)) {
            $field->defaultValue = $intType->maxValue;
        }

        $this->addField($field);

        // rule-int-regex
        $field = new RegExField("rule-int-regex", $typeFieldName, TextualColumn::TYPE_INTEGER);

        if (isset($intType)) {
            $field->defaultValue = $intType->regEx;
        }

        $this->addField($field);

        // // decimal //

        if ($column !== null && $column->type == TextualColumn::TYPE_DECIMAL) {
            /** @var DecimalType $decType */
            $decType = $column->getValidationType();
        }

        // rule-dec-integerdigits
        $field = new IntegerField("rule-dec-integerdigits", "Vorkommastellen");
        $field->dependsOn = $typeFieldName;
        $field->dependsOnValue = TextualColumn::TYPE_DECIMAL;
        $field->maxValue = 35;
        $field->minValue = 1;

        if (isset($decType)) {
            $field->defaultValue = $decType->integerDigits;
        }

        $this->addField($field);

        // rule-dec-decimaldigits
        $field = new IntegerField("rule-dec-decimaldigits", "Nachkommastellen");
        $field->dependsOn = $typeFieldName;
        $field->dependsOnValue = TextualColumn::TYPE_DECIMAL;
        $field->maxValue = 30;
        $field->minValue = 0;

        if (isset($decType)) {
            $field->defaultValue = $decType->decimalDigits;
        }

        $this->addField($field);

        // rule-dec-regex
        $field = new RegExField("rule-dec-regex", $typeFieldName, TextualColumn::TYPE_DECIMAL);

        if (isset($decType)) {
            $field->defaultValue = $decType->regEx;
        }

        $this->addField($field);

        // // bool //

        if ($column !== null && $column->type == TextualColumn::TYPE_BOOLEAN) {
            /** @var BooleanType $boolType */
            $boolType = $column->getValidationType();
        }

        // rule-bool-truelabel
        $field = new TextField("rule-bool-truelabel", "Bezeichnung für \"true\"");
        $field->dependsOn = $typeFieldName;
        $field->dependsOnValue = TextualColumn::TYPE_BOOLEAN;
        $field->maxLength = 30;
        $field->required = false;

        if (isset($boolType)) {
            $field->defaultValue = $boolType->trueLabel;
        }

        $this->addField($field);

        // rule-bool-falselabel
        $field = new TextField("rule-bool-falselabel", "Bezeichnung für \"false\"");
        $field->dependsOn = $typeFieldName;
        $field->dependsOnValue = TextualColumn::TYPE_BOOLEAN;
        $field->maxLength = 30;
        $field->required = false;

        if (isset($boolType)) {
            $field->defaultValue = $boolType->falseLabel;
        }

        $this->addField($field);

        // rule-bool-forcetrue
        $field = new CheckboxField("rule-bool-forcetrue", "Nur true als gültige Eingabe werten");
        $field->dependsOn = $typeFieldName;
        $field->dependsOnValue = TextualColumn::TYPE_BOOLEAN;
        $field->description = "Eigentlich sind true und false gültig, mit dieser Option ist nur true gültig";

        if (isset($boolType)) {
            $field->defaultValue = $boolType->forceTrue;
        }

        $this->addField($field);

        // // //

        // null-allowed
        $field = new CheckboxField("rule-null-allowed", "Angabe ist optional");
        $field->description = "Wenn kein Wert angegeben wird, wird NULL gespeichert";

        if (isset($column)) {
            $field->defaultValue = $column->getValidationType()->nullable;
        }

        $this->addField($field);

        // fill-value
        if (! isset($column) && ! $tableEmpty) {
            $field = new TextField("fill-value", "Füllwert");
            $field->description = "Dieser Wert wird für dieses Feld in die bestehenden Datensätze eingefügt";
            $field->required = false;

            $this->addField($field);
        }

        // description
        $this->addField(new ColumnDescriptionField($column));

        // position
        $this->addField(new ColumnPositionField(TextualColumn::loadList($tableId), $column));
    }

    /**
     * @throws Exception
     */
    public function perform(array $data)
    {
        if ($data["type"] == TextualColumn::TYPE_TEXT) {
            $type = new TextType();
            $type->fieldType = $data["rule-text-fieldtype"];
            $type->markdown = $data["rule-text-markdown"];

            if (! is_null($data["rule-text-maxlength"])) {
                $type->maxLength = intval($data["rule-text-maxlength"]);
            }

            if (! is_null($data["rule-text-minlength"])) {
                $type->minLength = intval($data["rule-text-minlength"]);
            }

            $type->nullable = $data["rule-null-allowed"];
            $type->regEx = $data["rule-text-regex"];
        } else if ($data["type"] == TextualColumn::TYPE_SELECTION) {
            $type = new SelectionType();
            $type->allowMultiple = $data["rule-select-allowmultiple"];
            $type->nullable = $data["rule-null-allowed"];
            $type->separator = $data["rule-select-separator"];

            foreach ($data["rule-select-options"] as $row) {
                $type->options[$row["name"]] = $row["label"];
            }
        } else if ($data["type"] == TextualColumn::TYPE_DATE) {
            $type = new DateType();
            $type->nullable = $data["rule-null-allowed"];
        } else if ($data["type"] == TextualColumn::TYPE_INTEGER) {
            $type = new IntegerType();

            if (! is_null($data["rule-int-maxdigits"])) {
                $type->maxDigits = intval($data["rule-int-maxdigits"]);
            }

            if (! is_null($data["rule-int-maxvalue"])) {
                $type->maxValue = intval($data["rule-int-maxvalue"]);
            }

            if (! is_null($data["rule-int-mindigits"])) {
                $type->minDigits = intval($data["rule-int-mindigits"]);
            }

            if (! is_null($data["rule-int-minvalue"])) {
                $type->minValue = intval($data["rule-int-minvalue"]);
            }

            $type->nullable = $data["rule-null-allowed"];
            $type->regEx = $data["rule-int-regex"];
        } else if ($data["type"] == TextualColumn::TYPE_DECIMAL) {
            $type = new DecimalType();
            $type->decimalDigits = intval($data["rule-dec-decimaldigits"]);
            $type->integerDigits = intval($data["rule-dec-integerdigits"]);
            $type->nullable = $data["rule-null-allowed"];
            $type->regEx = $data["rule-dec-regex"];
        } else if ($data["type"] == TextualColumn::TYPE_BOOLEAN) {
            $type = new BooleanType();
            $type->falseLabel = $data["rule-bool-falselabel"];
            $type->forceTrue = $data["rule-bool-forcetrue"];
            $type->nullable = $data["rule-null-allowed"];
            $type->trueLabel = $data["rule-bool-truelabel"];
        } else {
            throw new Exception("Unsupported type: ".$data["type"]);
        }

        if (is_null($this->column)) {
            // create
            $id = TextualColumn::create($this->tableId, $data["name"], $data["label"], $data["description"], $data["position"], $data["type"], $type);

            if (! $this->tableEmpty) {
                TextualField::fill($this->tableId, $id, $data["fill-value"], $type);
            }
        } else {
            // edit
            $this->column->edit($data["name"], $data["label"], $data["description"], $data["type"], $type);

            if ($this->column->position != $data["position"]) {
                $this->column->move(intval($data["position"]));
            }

            if (! $this->tableEmpty) {
                // TODO: Check if validation rules changed
                // TODO: Rerun validation
            }
        }

        Application::$instance->redirect("projects/$this->projectId/tables/$this->tableId", "saved");
    }
}
