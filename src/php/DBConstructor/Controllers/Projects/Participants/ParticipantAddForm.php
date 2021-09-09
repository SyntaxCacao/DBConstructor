<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\Projects\Participants;

use DBConstructor\Application;
use DBConstructor\Forms\Fields\SelectField;
use DBConstructor\Forms\Form;
use DBConstructor\Models\Participant;
use DBConstructor\Models\User;

class ParticipantAddForm extends Form
{
    /** @var string */
    public $projectId;

    public function __construct()
    {
        parent::__construct("participant-add-form");
    }

    public function init(string $projectId)
    {
        $this->projectId = $projectId;

        $field = new SelectField("user", "Benutzer");
        $users = User::loadNotParticipatingList($projectId);

        foreach ($users as $user) {
            $field->addOption($user->id, $user->lastname.", ".$user->firstname);
        }

        $this->addField($field);

        $field = new SelectField("role", "Rolle");
        $field->addOption("participant", "Beteiligter");
        $field->addOption("manager", "Manager");

        $this->addField($field);
    }

    public function perform(array $data)
    {
        Participant::create($data["user"], $this->projectId, $data["role"] === "manager");
        Application::$instance->redirect("projects/$this->projectId/participants", "added");
    }
}
