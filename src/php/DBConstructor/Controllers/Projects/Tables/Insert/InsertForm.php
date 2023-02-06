<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\Projects\Tables\Insert;

use DBConstructor\Application;
use DBConstructor\Controllers\Projects\ProjectsController;
use DBConstructor\Controllers\Projects\Tables\RowForm;
use DBConstructor\Forms\Fields\CheckboxField;
use DBConstructor\Forms\Fields\MarkdownField;
use DBConstructor\Forms\Fields\SelectField;
use DBConstructor\Models\Participant;
use DBConstructor\Models\RelationalColumn;
use DBConstructor\Models\RelationalField;
use DBConstructor\Models\Row;
use DBConstructor\Models\Table;
use DBConstructor\Models\TextualColumn;
use DBConstructor\Models\TextualField;
use DBConstructor\Util\JsonException;
use DBConstructor\Util\MarkdownParser;
use DBConstructor\Validation\Types\SelectionType;
use Exception;

class InsertForm extends RowForm
{
    /** @var bool */
    public $api = false;

    /** @var string|null */
    public $insertedId;

    /** @var string|null */
    public $next;

    /** @var Table */
    public $table;

    public function __construct()
    {
        parent::__construct("table-insert", false);
    }

    /**
     * @param array<RelationalColumn> $relationalColumns
     * @param array<TextualColumn> $textualColumns
     * @throws Exception
     * @throws JsonException
     */
    public function init(Table $table, array $relationalColumns, array $textualColumns, bool $nextNew = false)
    {
        $this->table = $table;
        $this->relationalColumns = $relationalColumns;
        $this->textualColumns = $textualColumns;

        foreach ($relationalColumns as $column) {
            $this->addRelationalField($column);
        }

        foreach ($textualColumns as $column) {
            $this->addTextualField($column);
        }

        // comment
        $field = new MarkdownField("comment", "Kommentar");
        $field->description = "Halten Sie hier etwa Unklarheiten bei der Datenerfassung fest";
        $field->larger = false;
        $field->maxLength = Row::MAX_COMMENT_LENGTH;
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

        $participants = Participant::loadList(ProjectsController::$projectId);

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

            $type = $column->getValidationType();
            $validator = $type->buildValidator();
            $field["valid"] = $validator->validate($field["value"]);

            if ($type instanceof SelectionType && $type->allowMultiple) {
                $field["value"] = TextualColumn::encodeOptions($field["value"]);
            }

            $textualFields[] = $field;
        }

        // Database insertion

        $this->insertedId = Row::create($this->table->id, Application::$instance->user->id, $this->api, $data["comment"], $data["flag"], $data["assignee"]);

        if (count($relationalFields) > 0) {
            // Validity may be set incorrectly when referencing same row
            // Referencing same row may not be possible on insertion, but maybe when editing?
            RelationalField::createAll($this->insertedId, $relationalFields);
        }

        if (count($textualFields) > 0) {
            TextualField::createAll($this->insertedId, $textualFields);
        }

        Row::revalidate($this->insertedId);

        // Next

        if ($data["next"] == "show") {
            Application::$instance->redirect("projects/".ProjectsController::$projectId."/tables/{$this->table->id}/view/$this->insertedId");
        } else {
            $this->next = $data["next"];
        }
    }

    public function generateAdditionalFields()
    {
        // TODO Find a better solution than this function
        echo '<hr style="margin: 32px 0">';
        echo $this->fields["comment"]->generateGroup();
        echo $this->fields["flag"]->generateGroup();
        echo $this->fields["assignee"]->generateGroup();
        echo $this->fields["next"]->generateGroup();
    }

    public function generateFields()
    {
        if ($this->table->instructions !== null) {
            echo '<div class="markdown">'.MarkdownParser::parse($this->table->instructions).'</div>';
        }

        parent::generateFields();
    }
}
