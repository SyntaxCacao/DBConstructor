<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\Projects\Participants;

use DBConstructor\Application;
use DBConstructor\Controllers\Projects\ProjectsController;
use DBConstructor\Forms\Fields\SelectField;
use DBConstructor\Forms\Form;
use DBConstructor\Models\Participant;
use DBConstructor\Models\User;

class ParticipantAddForm extends Form
{
    public function __construct()
    {
        parent::__construct("participant-add-form");
    }

    public function init()
    {
        $field = new SelectField("user", "Benutzer");
        $users = User::loadNotParticipatingList(ProjectsController::$projectId);

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
        Participant::add($data["user"], ProjectsController::$projectId, $data["role"] === "manager");
        Application::$instance->redirect("projects/".ProjectsController::$projectId."/participants", "added");
    }
}
