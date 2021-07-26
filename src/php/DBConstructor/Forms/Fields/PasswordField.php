<?php

declare(strict_types=1);

namespace DBConstructor\Forms\Fields;

class PasswordField extends TextField
{
    //public $callback;

    /**
     * @param string|null $label
     */
    public function __construct(string $name, $label = null)
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

    /*
    public function validate()
    {
        $callback = $this->callback;

        if (! is_null($callback) && ! $callback($this->value)) {
            return ["Das eingegebene Passwort ist falsch."];
        }

        return parent::validate();
    }
    */
}
