<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\Projects\Tables\View;

use DBConstructor\Application;
use DBConstructor\Forms\Fields\SelectField;
use DBConstructor\Forms\Form;
use DBConstructor\Models\Participant;
use DBConstructor\Models\Row;

class AssigneeForm extends Form
{
    /** @var array<string, Participant> */
    public $participants;

    /** @var Row */
    public $row;

    public function __construct()
    {
        parent::__construct("table-view-assignee");
    }

    /**
     * @param array<string, Participant> $participants
     */
    public function init(Row &$row, array &$participants, string $assigneeId = null)
    {
        $this->row = &$row;
        $this->participants = &$participants;

        $field = new SelectField("assignee", "Zuweisung");
        $field->required = false;

        if ($assigneeId === Application::$instance->user->id) {
            $field->addOption(Application::$instance->user->id, "Mir zugewiesen");
        } else {
            $field->addOption(Application::$instance->user->id, "Mir zuweisen");
        }

        foreach ($participants as $participant) {
            if ($participant->userId !== Application::$instance->user->id) {
                $field->addOption($participant->userId, $participant->lastName.", ".$participant->firstName);
            }
        }

        $field->defaultValue = $assigneeId;

        $this->addField($field);
    }

    public function perform(array $data)
    {
        if ($data["assignee"] !== $this->row->assigneeId) {
            if ($data["assignee"] === null) {
                $this->row->assign(Application::$instance->user->id, false);
            } else {
                $this->row->assign(Application::$instance->user->id, false, $data["assignee"], $this->participants[$data["assignee"]]->firstName, $this->participants[$data["assignee"]]->lastName);
            }
        }
    }
}
