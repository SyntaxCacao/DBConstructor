<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\Projects\Tables\View;

use DBConstructor\Application;
use DBConstructor\Forms\Fields\SelectField;
use DBConstructor\Forms\Form;
use DBConstructor\Models\Participant;

class FilterForm extends Form
{
    public $validity;

    public $flagged;

    public $assignee;

    public $creator;

    public $order;

    public function __construct()
    {
        parent::__construct("filter");
        // Hack to make Form class always process this form
        $_REQUEST["form-name"] = $this->name;
    }

    public function generate(): string
    {
        $html = '<form class="page-table-view-controls" action="" method="get">';

        foreach ($this->fields as $field) {
            $html .= $field->generateField();
        }

        $html .= $this->generateActions();
        $html .= '</form>';

        return $html;
    }

    /**
     * @param array<Participant> $participants
     */
    public function init(array $participants)
    {
        $this->buttonLabel = "Aktualisieren";

        // validity
        $field = new SelectField("validity");
        $field->required = false;
        $field->nullLabel = "Gültigkeit: –";
        $field->addOption("valid", "Nur gültige Datensätze");
        $field->addOption("invalid", "Nur ungültige Datensätze");

        $this->addField($field);

        // flagged
        $field = new SelectField("flagged");
        $field->required = false;
        $field->nullLabel = "Kennzeichnung: –";
        $field->addOption("flagged", "Nur gekennzeichnete Datensätze");

        $this->addField($field);

        // assignee
        $field = new SelectField("assignee");
        $field->required = false;
        $field->nullLabel = "Zuweisung: –";
        $field->addOption(Application::$instance->user->id, "Mir zugewiesen");

        foreach ($participants as $participant) {
            if ($participant->userId !== Application::$instance->user->id) {
                $field->addOption($participant->userId, $participant->lastName.", ".$participant->firstName." zugewiesen");
            }
        }

        $this->addField($field);

        // creator
        $field = new SelectField("creator");
        $field->required = false;
        $field->nullLabel = "Angelegt von: –";
        $field->addOption(Application::$instance->user->id, "Von mir angelegt");

        foreach ($participants as $participant) {
            if ($participant->userId !== Application::$instance->user->id) {
                $field->addOption($participant->userId, "Von ".$participant->lastName.", ".$participant->firstName." angelegt");
            }
        }

        $this->addField($field);

        // order
        $field = new SelectField("order");
        $field->required = false;
        $field->nullLabel = "Sortieren: Letzte Aktivität";
        $field->addOption("creation", "Sortieren: Zuletzt angelegt");

        $this->addField($field);
    }

    public function perform(array $data)
    {
        foreach ($this->fields as $field) {
            $name = $field->name;

            if (isset($data[$field->name])) {
                $this->$name = $data[$field->name];
            } else if (isset($field->defaultValue)) {
                $this->$name = $field->defaultValue;
            }
        }
    }
}
