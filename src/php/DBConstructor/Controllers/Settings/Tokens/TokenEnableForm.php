<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\Settings\Tokens;

use DBConstructor\Application;
use DBConstructor\Models\AccessToken;

class TokenEnableForm extends TokenForm
{
    /** @var AccessToken */
    public $token;

    public function __construct()
    {
        parent::__construct("reaktivieren", "reaktiviert");
    }

    public function init(AccessToken $token)
    {
        $this->token = $token;

        if ($this->token->expired) {
            $this->addExpirationField();
        }

        $this->addPasswordField();
    }

    public function perform(array $data)
    {
        if ($this->token->expired) {
            $this->newToken = $this->token->renew($data["expires"]);
        }

        $this->token->setDisabled(false);
        Application::$instance->redirect("settings/tokens", "saved");
    }
}
