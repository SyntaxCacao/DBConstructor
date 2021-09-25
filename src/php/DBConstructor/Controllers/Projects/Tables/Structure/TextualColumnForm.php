<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\Projects\Tables\Structure;

use DBConstructor\Application;
use DBConstructor\Forms\Fields\CheckboxField;
use DBConstructor\Forms\Fields\IntegerField;
use DBConstructor\Forms\Fields\SelectField;
use DBConstructor\Forms\Fields\TextField;
use DBConstructor\Forms\Fields\ValidationClosure;
use DBConstructor\Forms\Form;
use DBConstructor\Models\TextualColumn;
use DBConstructor\Validation\Validator;
use Exception;

class TextualColumnForm extends Form
{
    /** @var TextualColumn */
    public $column;

    /** @var string */
    public $projectId;

    /** @var string */
    public $tableId;

    public function __construct()
    {
        parent::__construct("textual-column-form");
    }

    /**
     * @param TextualColumn|null $column null on creation
     */
    public function init(string $projectId, string $tableId, string $tablePosition, TextualColumn $column = null)
    {
        $this->projectId = $projectId;
        $this->tableId = $tableId;
        $this->column = $column;

        // label
        $this->addField(new ColumnLabelField($column));

        // name
        $this->addField(new ColumnNameField($tableId, $column));

        if (is_null($column)) {
            // type
            $typeFieldName = "type";
            $field = new SelectField($typeFieldName, "Datentyp");
            $field->addOptions(TextualColumn::TYPES);
            $this->addField($field);

            // rules
            $field = new CheckboxField("rule-integer-unsigned", "Nur positive Zahlen");
            $field->description = "Unter Einschluss von 0.";
            $field->dependsOn = $typeFieldName;
            $field->dependsOnValue = TextualColumn::TYPE_INTEGER;
            $this->addField($field);

            $field = new IntegerField("rule-integer-min", "Mindestwert");
            $field->dependsOn = $typeFieldName;
            $field->dependsOnValue = TextualColumn::TYPE_INTEGER;
            $field->required = false;
            $this->addField($field);

            $field = new IntegerField("rule-integer-max", "Höchster Wert");
            $field->dependsOn = $typeFieldName;
            $field->dependsOnValue = TextualColumn::TYPE_INTEGER;
            $field->required = false;
            $this->addField($field);

            $field = new IntegerField("rule-text-minlength", "Mindestlänge");
            $field->dependsOn = $typeFieldName;
            $field->dependsOnValue = TextualColumn::TYPE_TEXT;
            $field->required = false;
            $this->addField($field);

            $field = new IntegerField("rule-text-maxlength", "Maximallänge");
            $field->dependsOn = $typeFieldName;
            $field->dependsOnValue = TextualColumn::TYPE_TEXT;
            $field->required = false;
            $this->addField($field);

            $field = new TextField("rule-text-regex", "Regulärer Ausdruck");
            $field->dependsOn = $typeFieldName;
            $field->dependsOnValue = TextualColumn::TYPE_TEXT;
            $field->monospace = true;
            $field->required = false;
            $field->validationClosures[] = new ValidationClosure(function ($value) {
                // https://stackoverflow.com/a/12941133/5489107
                // @ to suppress error messages resulting from invalid regex
                return ! (@preg_match("/".$value."/", "") === false);
            }, "Geben Sie einen gültigen regulären Ausdruck ein.");
            $this->addField($field);

            $field = new CheckboxField("rule-null-allowed", "Angabe ist optional");
            $field->description = "Wenn kein Wert angegeben wird, wird NULL gespeichert.";
            $this->addField($field);
        }

        // description
        $this->addField(new ColumnDescriptionField($column));
    }

    /**
     * @throws Exception
     */
    public function perform(array $data)
    {
        if (is_null($this->column)) {
            // create
            $rules = null;

            if ($data["type"] == TextualColumn::TYPE_INTEGER) {
                $rules = Validator::createIntegerValidator(! $data["rule-null-allowed"], $data["rule-integer-unsigned"], $data["rule-integer-min"], $data["rule-integer-max"])->toJSON();
            } else if ($data["type"] == TextualColumn::TYPE_TEXT) {
                $rules = Validator::createTextValidator(! $data["rule-null-allowed"], $data["rule-text-minlength"], $data["rule-text-maxlength"], $data["rule-text-regex"])->toJSON();
            }

            TextualColumn::create($this->tableId, $data["name"], $data["label"], $data["description"], $data["type"], $rules);
        }  else {
            // edit
            $this->column->edit($data["name"], $data["label"], $data["description"]);

            // TODO: Rerun validation
        }

        Application::$instance->redirect("projects/$this->projectId/tables/$this->tableId", "saved");
    }
}
