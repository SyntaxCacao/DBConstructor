<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\Projects\Tables\Insert;

use DBConstructor\Application;
use DBConstructor\Forms\Fields\CheckboxField;
use DBConstructor\Forms\Fields\MarkdownField;
use DBConstructor\Forms\Fields\SelectField;
use DBConstructor\Forms\Fields\TextareaField;
use DBConstructor\Forms\Fields\TextField;
use DBConstructor\Forms\Form;
use DBConstructor\Models\Participant;
use DBConstructor\Models\RelationalColumn;
use DBConstructor\Models\RelationalField;
use DBConstructor\Models\Row;
use DBConstructor\Models\TextualColumn;
use DBConstructor\Models\TextualField;
use DBConstructor\Util\JsonException;
use DBConstructor\Validation\Types\BooleanType;
use DBConstructor\Validation\Types\SelectionType;
use DBConstructor\Validation\Types\TextType;
use Exception;

class InsertForm extends Form
{
    /** @var string|null */
    public $next;

    /** @var string */
    public $projectId;

    /** @var array<string, string> */
    public $relationalColumnFields = [];

    /** @var array<RelationalColumn> */
    public $relationalColumns;

    /** @var string */
    public $tableId;

    /** @var array<string, string> */
    public $textualColumnFields = [];

    /** @var array<TextualColumn> */
    public $textualColumns;

    public function __construct()
    {
        parent::__construct("table-insert");
    }

    /**
     * @param array<RelationalColumn> $relationalColumns
     * @param array<TextualColumn> $textualColumns
     * @throws Exception
     * @throws JsonException
     */
    public function init(string $projectId, string $tableId, array $relationalColumns, array $textualColumns, bool $nextNew = false)
    {
        $this->projectId = $projectId;
        $this->tableId = $tableId;
        $this->relationalColumns = $relationalColumns;
        $this->textualColumns = $textualColumns;

        foreach ($relationalColumns as $column) {
            $field = new RelationalSelectField($column);
            $this->addField($field);
            $this->relationalColumnFields[$column->name] = $this->fields[$field->name];
        }

        foreach ($textualColumns as $column) {
            $fieldName = "textual-".$column->id;

            if ($column->type == TextualColumn::TYPE_TEXT) {
                /** @var TextType $type */
                $type = $column->getValidationType();

                if ($type->fieldType == TextType::FIELD_INPUT) {
                    $field = new TextField($fieldName);
                } else {
                    if ($type->markdown == TextType::MARKDOWN_DISABLED) {
                        $field = new TextareaField($fieldName);
                    } else {
                        $field = new MarkdownField($fieldName);
                    }

                    $field->larger = $type->fieldType == TextType::FIELD_TEXTAREA_LARGE;
                }

                $field->maxLength = 10000; // TODO: Check
                $field->spellcheck = false;
            } else if ($column->type == TextualColumn::TYPE_SELECTION) {
                /** @var SelectionType $type */
                $type = $column->getValidationType();
                $field = new SelectField($fieldName);

                foreach ($type->options as $name => $label) {
                    $field->addOption($name, $label);
                }
            } else if ($column->type == TextualColumn::TYPE_DATE) {
                $field = new TextField($fieldName);
                $field->maxLength = 10000; // TODO: Check
                $field->spellcheck = false;
            } else if ($column->type == TextualColumn::TYPE_INTEGER) {
                $field = new TextField($fieldName);
                $field->maxLength = 10000; // TODO Check
                $field->spellcheck = false;
            } else if ($column->type == TextualColumn::TYPE_DECIMAL) {
                $field = new TextField($fieldName);
                $field->maxLength = 10000; // TODO Check
                $field->spellcheck = false;
            } else if ($column->type == TextualColumn::TYPE_BOOLEAN) {
                /** @var BooleanType $type */
                $type = $column->getValidationType();
                $field = new SelectField($fieldName);

                if (is_null($type->falseLabel)) {
                    $field->addOption(BooleanType::VALUE_FALSE, "false");
                } else {
                    $field->addOption(BooleanType::VALUE_FALSE, $type->falseLabel);
                }

                if (is_null($type->trueLabel)) {
                    $field->addOption(BooleanType::VALUE_TRUE, "true");
                } else {
                    $field->addOption(BooleanType::VALUE_TRUE, $type->trueLabel);
                }
            } else {
                throw new Exception("Unsupported column type: ".$column->type);
            }

            $field->required = false;
            $this->addField($field);

            $this->textualColumnFields[$column->name] = $this->fields[$field->name];
        }

        // comment
        $field = new MarkdownField("comment", "Kommentar");
        $field->description = "Halten Sie hier etwa Unklarheiten bei der Datenerfassung fest";
        $field->larger = false;
        $field->maxLength = 1000;
        $field->required = false;

        $this->addField($field);

        // flag
        $field = new CheckboxField("flag", "Zur Nachverfolgung kennzeichnen");
        $field->description = "Kennzeichen Sie diesen Datensatz, wenn noch Klärungsbedarf besteht";

        $this->addField($field);

        // assignee
        $field = new SelectField("assignee", "Jemandem zuordnen", "Keine Auswahl");
        $field->description = "Ordnen Sie den Datensatz einem Projektbeteiligten zur weiteren Bearbeitung zu";
        $field->required = false;

        $field->addOption(Application::$instance->user->id, "Mir zuordnen");

        $participants = Participant::loadList($projectId);

        foreach ($participants as $participant) {
            if ($participant->userId != Application::$instance->user->id) {
                $field->addOption($participant->userId, $participant->lastName.", ".$participant->firstName);
            }
        }

        $this->addField($field);

        // next
        $field = new SelectField("next", "Als nächstes");
        $field->addOption("show", "Neuen Datensatz anzeigen");
        $field->addOption("new", "Weiteren Datensatz anlegen");
        $field->addOption("duplicate", "Eingaben für weiteren Datensatz übernehmen");

        if ($nextNew) {
            // Cannot use defaultValue here, because defaultValue is inserted
            // only in Form#process() which will not be called in this case
            $field->value = "new";
        }

        $this->addField($field);
    }

    /**
     * @throws JsonException
     */
    public function perform(array $data)
    {
        // Assemble fields and perform validation for textual fields

        $relationalFields = [];

        foreach ($this->relationalColumns as $column) {
            $field = [];
            $field["column_id"] = $column->id;
            $field["column_nullable"] = $column->nullable;
            $field["target_row_id"] = $data["relational-".$column->id];
            $relationalFields[] = $field;
        }

        $textualFields = [];

        foreach ($this->textualColumns as $column) {
            $field = [];
            $field["column_id"] = $column->id;
            $field["value"] = $data["textual-".$column->id];

            $validator = $column->getValidationType()->buildValidator();
            $field["valid"] = $validator->validate($field["value"]);

            $textualFields[] = $field;
        }

        // Database insertion

        $id = Row::create($this->tableId, Application::$instance->user->id, $data["assignee"], $data["flag"]);

        if (count($relationalFields) > 0) {
            // Validity may be set incorrectly when referencing same row
            // Referencing same row may not be possible on insertion, but maybe when editing?
            RelationalField::createAll($id, $relationalFields);
        }

        if (count($textualFields) > 0) {
            TextualField::createAll($id, $textualFields);
        }

        Row::updateValidity($id);

        // Next

        if ($data["next"] == "show") {
            Application::$instance->redirect("projects/$this->projectId/tables/$this->tableId/view/$id");
        } else {
            $this->next = $data["next"];
        }
    }

    public function generateAdditionalFields(): string
    {
        // TODO
        return $this->fields["comment"]->generateGroup().$this->fields["flag"]->generateGroup().$this->fields["assignee"]->generateGroup().$this->fields["next"]->generateGroup();
    }
}
