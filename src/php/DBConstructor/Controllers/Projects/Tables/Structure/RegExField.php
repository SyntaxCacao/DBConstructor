<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\Projects\Tables\Structure;

use DBConstructor\Forms\Fields\TextField;
use DBConstructor\Forms\Fields\ValidationClosure;

class RegExField extends TextField
{
    public function __construct(string $name, string $dependsOn, string $dependsValue)
    {
        parent::__construct($name, "Regulärer Ausdruck");
        $this->dependsOn = $dependsOn;
        $this->dependsOnValue = $dependsValue;
        $this->maxLength = 350;
        $this->monospace = true;
        $this->placeholder = "/^[a-z]+$/";
        $this->required = false;
        $this->validationClosures[] = new ValidationClosure(static function ($value) {
            // https://stackoverflow.com/a/12941133/5489107
            // @ to suppress error messages resulting from invalid regex
            return ! (@preg_match($value, "") === false);
        }, "Geben Sie einen gültigen regulären Ausdruck ein.");
    }
}
