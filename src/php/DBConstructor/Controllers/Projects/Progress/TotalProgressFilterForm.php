<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\Projects\Progress;

use DBConstructor\Application;
use DBConstructor\Forms\Fields\SelectField;
use DBConstructor\Forms\Form;
use DBConstructor\Models\Participant;
use DBConstructor\Models\RowProgressLoader;
use DBConstructor\Models\Table;

class TotalProgressFilterForm extends Form
{
    public $includeApi = false;

    public $period = RowProgressLoader::PERIOD_ALLTIME;

    public $tableId = null;

    public $userId = null;

    public function __construct()
    {
        parent::__construct("filter");
    }

    public function generate(): string
    {
        $html = $this->generateStartingTag();
        $html .= '<div class="page-table-view-controls-row">';

        foreach ($this->fields as $field) {
            $html .= $field->generateField();
        }

        $html .= '<button class="button" type="submit"><span class="bi bi-arrow-clockwise no-margin"></span><span class="hide-up-md" style="margin-left: 6px">Aktualisieren</span></button>';
        $html .= '</div>';
        $html .= $this->generateClosingTag();
        return $html;
    }

    /**
     * @param array<Table> $tables
     * @param array<Participant> $participants
     */
    public function init(array $tables, array $participants = null)
    {
        // table
        $field = new SelectField("table");
        $field->required = false;
        $field->nullLabel = "Tabellen: Alle";

        foreach ($tables as $table) {
            $field->addOption($table->id, $table->label);
        }

        $this->addField($field);

        if ($participants !== null) {
            // participant
            $field = new SelectField("participant");
            $field->required = false;
            $field->nullLabel = "Personen: Alle";

            if (array_key_exists(Application::$instance->user->id, $participants)) {
                $field->addOption(Application::$instance->user->id, "Ich selbst");
            }

            foreach ($participants as $participant) {
                if (Application::$instance->user->id !== $participant->userId) {
                    $field->addOption($participant->userId, "$participant->lastName, $participant->firstName");
                }
            }

            $this->addField($field);
        }

        // api
        $field = new SelectField("api");
        $field->addOption("exclude", "API: Ausgeschlossen");
        $field->addOption("include", "API: Eingeschlossen");

        $this->addField($field);

        // period
        $field = new SelectField("period");

        $periods = [
            RowProgressLoader::PERIOD_ALLTIME => "Zeitraum: Vollständig",
            RowProgressLoader::PERIOD_YEAR => "Letztes Jahr",
            RowProgressLoader::PERIOD_MONTH_6 => "Letzte 6 Monate",
            RowProgressLoader::PERIOD_MONTH_3 => "Letzte 3 Monate"
        ];

        foreach ($periods as $period => $label) {
            $field->addOption((string) $period, $label);
        }

        $this->addField($field);
    }

    public function perform(array $data)
    {
        $this->tableId = $data["table"];
        $this->userId = $data["participant"] ?? null;
        $this->includeApi = $data["api"] === "include";
        $this->period = intval($data["period"]);
    }
}
