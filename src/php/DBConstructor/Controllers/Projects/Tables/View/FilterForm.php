<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\Projects\Tables\View;

use DBConstructor\Application;
use DBConstructor\Forms\Fields\SelectField;
use DBConstructor\Forms\Fields\TextField;
use DBConstructor\Forms\Form;
use DBConstructor\Models\Participant;
use DBConstructor\Models\RelationalColumn;
use DBConstructor\Models\RowLoader;
use DBConstructor\Models\TextualColumn;

class FilterForm extends Form
{
    /** @var bool */
    public $expand = false;

    /** @var RowLoader */
    public $loader;

    /** @var array<int, array<string>> */
    public $rows = [0 => [], 1 => [], 2 => []];

    /** @var bool */
    public $showExportIdColumn = false;

    public function __construct(string $tableId)
    {
        parent::__construct("filter");
        $this->loader = new RowLoader($tableId);

        // Hack to make Form class always process this form
        $_REQUEST["form-name"] = $this->name;
    }

    public function generate(): string
    {
        $html = '<form class="page-table-view-controls" action="" method="get">';

        $html .= '<div class="page-table-view-controls-row">';
        $html .= '<a class="button hide-down-md page-table-view-controls-expand'.($this->expand ? ' button-selected' : '').'" href="#" title="Weitere Filter zeigen"><span class="bi bi-chevron-down no-margin"></span></a>';

        foreach ($this->rows[0] as $fieldName) {
            $html .= $this->fields[$fieldName]->generateField();
        }

        $html .= '<a class="button hide-up-md page-table-view-controls-expand'.($this->expand ? ' button-selected' : '').'" href="#" title="Weitere Filter zeigen"><span class="bi bi-chevron-down no-margin"></span></a>';
        $html .= '<button class="button hide-down-md" type="submit"><span class="bi bi-arrow-clockwise"></span>Aktualisieren</button>';
        $html .= '</div>';

        for ($i = 1; $i < count($this->rows); $i++) {
            $html .= '<div class="page-table-view-controls-row page-table-view-controls-row-expandable'.($this->expand ? ' expanded' : '').'">';

            foreach ($this->rows[$i] as $fieldName) {
                $html .= $this->fields[$fieldName]->generateField();
            }

            $html .= '</div>';
        }

        $html .= '<button class="button hide-up-md" type="submit"><span class="bi bi-arrow-clockwise"></span>Aktualisieren</button>';
        $html .= '</form>';

        return $html;
    }

    /**
     * @param array<Participant> $participants
     * @param array<RelationalColumn> $relationalColumns
     * @param array<TextualColumn> $textualColumns
     */
    public function init(array $participants, array $relationalColumns, array $textualColumns)
    {
        // validity
        $field = new SelectField("validity");
        $field->required = false;
        $field->nullLabel = "Gültigkeit: –";
        $field->addOption(RowLoader::FILTER_VALIDITY_VALID, "Nur gültige Datensätze");
        $field->addOption(RowLoader::FILTER_VALIDITY_INVALID, "Nur ungültige Datensätze");

        $this->addField($field);
        $this->rows[0][] = $field->name;

        // flagged
        $field = new SelectField("flagged");
        $field->required = false;
        $field->nullLabel = "Kennzeichnung: –";
        $field->addOption(RowLoader::FILTER_FLAGGED, "Nur gekennzeichnete Datensätze");
        $field->addOption(RowLoader::FILTER_FLAGGED_COMMENTED, "Nur kommentierte Datensätze");

        $this->addField($field);
        $this->rows[0][] = $field->name;

        // assignee
        $field = new SelectField("assignee");
        $field->required = false;
        $field->nullLabel = "Zuweisung: –";
        $field->addOption(Application::$instance->user->id, "Mir zugewiesen");
        $field->addOption(RowLoader::FILTER_ASSIGNEE_ANYONE, "Irgendjemandem zugewiesen");

        foreach ($participants as $participant) {
            if ($participant->userId !== Application::$instance->user->id) {
                $field->addOption($participant->userId, $participant->lastName.", ".$participant->firstName." zugewiesen");
            }
        }

        $this->addField($field);
        $this->rows[0][] = $field->name;

        // creator
        $field = new SelectField("creator");
        $field->required = false;
        $field->nullLabel = "Angelegt von: –";
        $field->addOption(Application::$instance->user->id, "Angelegt von: Mir");

        foreach ($participants as $participant) {
            if ($participant->userId !== Application::$instance->user->id) {
                $field->addOption($participant->userId, "Von ".$participant->lastName.", ".$participant->firstName." angelegt");
            }
        }

        $this->addField($field);
        $this->rows[0][] = $field->name;

        // createdAfter
        $field = new TextField("createdAfter");
        $field->maxLength = 11;
        $field->placeholder = "Angelegt ab (JJJJ-MM-TT)";
        $field->required = false;

        $this->addField($field);
        $this->rows[1][] = $field->name;

        // createdBefore
        $field = new TextField("createdBefore");
        $field->maxLength = 11;
        $field->placeholder = "Angelegt bis (JJJJ-MM-TT)";
        $field->required = false;

        $this->addField($field);
        $this->rows[1][] = $field->name;

        // updatedBy
        $field = new SelectField("updatedBy");
        $field->required = false;
        $field->nullLabel = "Verändert von: –";
        $field->addOption(Application::$instance->user->id, "Verändert von: Mir");

        foreach ($participants as $participant) {
            if ($participant->userId !== Application::$instance->user->id) {
                $field->addOption($participant->userId, "Verändert von: ". $participant->lastName.", ".$participant->firstName);
            }
        }

        $this->addField($field);
        $this->rows[1][] = $field->name;

        // deleted
        $field = new SelectField("deleted");
        $field->required = false;
        $field->nullLabel = "Gelöschtes: Ausblenden";
        $field->addOption(RowLoader::FILTER_DELETED_INCLUDE, "Gelöschtes: Einschließen");
        $field->addOption(RowLoader::FILTER_DELETED_ONLY, "Im Papierkorb suchen");

        $this->addField($field);
        $this->rows[1][] = $field->name;

        // searchColumn
        $field = new SelectField("searchColumn");
        $field->required = false;
        $field->nullLabel = "Suche in Feld: –";
        $field->addOption(RowLoader::SEARCH_COLUMN_ID, "Suche in Feld: ID");
        $field->addOption(RowLoader::SEARCH_COLUMN_EXPORT_ID, "Suche in Feld: Letzte Export-ID");

        foreach ($relationalColumns as $column) {
            $field->addOption("rel-".$column->id, "Suche in Feld: ".$column->label);
        }

        foreach ($textualColumns as $column) {
            $field->addOption("txt-".$column->id, "Suche in Feld: ".$column->label);
        }

        $field->addOption(RowLoader::SEARCH_COLUMN_ALL_TEXTUAL, "Suche in allen Wertfeldern");

        $this->addField($field);
        $this->rows[2][] = $field->name;

        // searchValue
        $field = new TextField("searchValue");
        $field->maxLength = 200;
        $field->placeholder = "Suchbegriff";
        $field->required = false;

        $this->addField($field);
        $this->rows[2][] = $field->name;

        // order
        $field = new SelectField("order");
        $field->required = false;
        $field->nullLabel = "Sortieren: Letzte Aktivität";
        $field->addOption(RowLoader::ORDER_BY_CREATED, "Sortieren: Zuletzt angelegt");

        foreach ($textualColumns as $column) {
            $field->addOption($column->id, "Sortieren: ".$column->label);
        }

        $this->addField($field);
        $this->rows[2][] = $field->name;

        // orderDirection
        $field = new SelectField("orderDirection");
        $field->required = false;
        $field->nullLabel = "Sortieren: Absteigend (Z–A)";
        $field->addOption(RowLoader::ORDER_DIRECTION_ASCENDING, "Sortieren: Aufsteigend (A–Z)");

        $this->addField($field);
        $this->rows[2][] = $field->name;
    }

    public function perform(array $data)
    {
        foreach ($this->fields as $field) {
            $fieldName = $field->name;
            if ($fieldName !== "searchColumn" && $fieldName !== "searchValue" && $data[$fieldName] !== null) {
                $this->loader->$fieldName = $data[$field->name];
            }
        }

        if ($data["searchColumn"] !== null) {
            $this->loader->addSearch($data["searchColumn"], $data["searchValue"]);
            $this->showExportIdColumn = $data["searchColumn"] === RowLoader::SEARCH_COLUMN_EXPORT_ID;
        }

        for ($i = 1; $i < count($this->rows); $i++) {
            foreach ($this->rows[$i] as $fieldName) {
                if ($data[$fieldName] !== null) {
                    $this->expand = true;
                }
            }
        }
    }
}
