<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\Settings\Tokens;

use DBConstructor\Application;
use DBConstructor\Models\AccessToken;
use DBConstructor\Models\Project;

class TokenEditForm extends TokenForm
{
    /** @var array<Project> */
    public $projects;

    /** @var AccessToken */
    public $token;

    public function __construct()
    {
        parent::__construct("bearbeiten", "bearbeitet");
    }

    public function init(AccessToken $token)
    {
        $this->projects = Project::loadParticipating(Application::$instance->user->id);
        $this->token = $token;

        $this->addLabelField();
        $this->addExpirationField(true);
        $this->addScopeField($this->projects, $this->token);
        $this->addPasswordField();
    }

    public function perform(array $data)
    {
        $this->token->edit($data["label"], $this->processScope($this->projects, $data));

        if ($data["expires"] === null) {
            Application::$instance->redirect("settings/tokens", "saved");
        } else {
            $this->newToken = $this->token->renew($data["expires"]);
        }
    }
}
