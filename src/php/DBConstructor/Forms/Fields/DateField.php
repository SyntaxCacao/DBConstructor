<?php

declare(strict_types=1);

namespace DBConstructor\Forms\Fields;

class DateField extends TextField
{
    public function __construct(string $name, string $label = null)
    {
        parent::__construct($name, $label);
        $this->type = "date";
    }

    public function validate(): array
    {
        if (! preg_match("/^\d{4}-\d{2}-\d{2}$/", $this->value)) {
            return ["Geben Sie ein gültiges Datum ein."];
        }

        $result = date_parse($this->value);

        if ($result["warning_count"] > 0 || $result["error_count"] > 0) {
            return ["Geben Sie ein gültiges Datum ein."];
        }

        return [];
    }
}
