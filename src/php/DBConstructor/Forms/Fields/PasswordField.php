<?php

declare(strict_types=1);

namespace DBConstructor\Forms\Fields;

class PasswordField extends TextField
{
    public function __construct(string $name, string $label = null)
    {
        parent::__construct($name, $label);
        $this->type = "password";
    }

    public function generateField(bool $placeholderLabel = false): string
    {
        // passwords must not to be inserted as default values
        $this->value = null;
        return parent::generateField($placeholderLabel);
    }
}
