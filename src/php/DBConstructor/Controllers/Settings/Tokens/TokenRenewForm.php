<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\Settings\Tokens;

use DBConstructor\Models\AccessToken;

class TokenRenewForm extends TokenForm
{
    /** @var AccessToken */
    public $token;

    public function __construct()
    {
        parent::__construct("erneuern", "erneuert");
    }

    public function init(AccessToken $token)
    {
        $this->token = $token;

        $this->addExpirationField();
        $this->addPasswordField();
    }

    public function perform(array $data)
    {
        $this->newToken = $this->token->renew($data["expires"]);
    }
}
