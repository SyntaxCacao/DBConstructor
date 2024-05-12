<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\Projects\Progress;

use DateTime;
use DBConstructor\Forms\Fields\DateField;
use DBConstructor\Forms\Fields\SelectField;
use DBConstructor\Forms\Form;

class TotalUserProgressFilterForm extends Form
{
    public $endDate = null;

    public $includeApi = false;

    public $tableId = null;

    public function __construct()
    {
        parent::__construct("filter");

        // Hack to make Form class always process this form (hidden "form-name" input is not present in this form)
        //$_REQUEST["form-name"] = $this->name;
    }

    public function generate(): string
    {
        //$html = '<form action="" method="get">';
        $html = $this->generateStartingTag();
        $html .= '<div class="page-table-view-controls-row">';

        foreach ($this->fields as $field) {
            $html .= $field->generateField();
        }

        $html .= '<button class="button" type="submit"><span class="bi bi-arrow-clockwise no-margin"></span><span class="hide-up-md" style="margin-left: 6px">Aktualisieren</span></button>';
        $html .= '</div>';
        $html .= $this->generateClosingTag();
        //$html .= '</form>';
        return $html;
    }

    public function init(array $tables)
    {
        // table
        $field = new SelectField("table");
        $field->required = false;
        $field->nullLabel = "Tabellen: Alle";

        foreach ($tables as $table) {
            $field->addOption($table->id, $table->label);
        }

        $this->addField($field);

        // api
        $field = new SelectField("api");
        $field->addOption("exclude", "API: Ausgeschlossen");
        $field->addOption("include", "API: Eingeschlossen");

        $this->addField($field);

        // endDate
        $field = new DateField("enddate");
        $field->value = (new DateTime())->format("Y-m-d");

        $this->addField($field);
    }

    public function perform(array $data)
    {
        $this->tableId = $data["table"];
        $this->includeApi = $data["api"] === "include";
        $this->endDate = $data["enddate"];
    }
}
