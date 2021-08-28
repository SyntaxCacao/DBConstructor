<?php

declare(strict_types=1);

namespace DBConstructor\Forms\Fields;

class MarkdownField extends TextareaField
{
    public function __construct(string $name, string $label = null, bool $larger = true)
    {
        parent::__construct($name, $label);
        $this->footer = "Formatierung mit Markdown: *kursiv*, **fett**, [Linktext](https://www.fu-berlin.de/), # Überschrift 1, ## Überschrift 2, ### Überschrift 3";
        $this->larger = $larger;
        $this->monospace = true;
    }
}
