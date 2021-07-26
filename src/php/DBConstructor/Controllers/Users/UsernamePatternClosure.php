<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\Users;

use DBConstructor\Forms\Fields\ValidationClosure;

class UsernamePatternClosure extends ValidationClosure
{
    public function __construct()
    {
        parent::__construct(static function ($value) {
            return preg_match("/^[A-Za-z0-9-_.]+$/", $value);
        }, "Benutzernamen dürfen nur alphanumerische Zeichen, Bindestriche, Unterstriche und Punkte enthalten.", true);
    }
}
