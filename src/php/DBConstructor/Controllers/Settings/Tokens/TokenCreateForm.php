<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\Settings\Tokens;

use DBConstructor\Application;
use DBConstructor\Models\AccessToken;
use DBConstructor\Models\Project;

class TokenCreateForm extends TokenForm
{
    /** @var array<Project> */
    public $projects;

    public function __construct()
    {
        parent::__construct("anlegen", "angelegt");
    }

    public function init()
    {
        $this->projects = Project::loadParticipating(Application::$instance->user->id);

        $this->addLabelField();
        $this->addExpirationField();
        $this->addScopeField($this->projects);
        $this->addPasswordField();
    }

    public function perform(array $data)
    {
        $scope = $this->processScope($this->projects, $data);
        $this->newToken = AccessToken::create(Application::$instance->user->id, $data["label"], $data["expires"], $scope);
    }
}
