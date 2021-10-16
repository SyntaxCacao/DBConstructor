<?php

declare(strict_types=1);

namespace DBConstructor\Forms\Fields;

class MarkdownField extends TextareaField
{
    public function __construct(string $name, string $label = null, bool $larger = true)
    {
        parent::__construct($name, $label);
        $this->id = "js-markdown-field-".$this->name;
        $this->larger = $larger;
        $this->monospace = true;
    }

    public function generateGroup(array $errorMessages = []): string
    {
        $html = '<div class="form-group';

        if (isset($this->dependsOn)) {
            $html .= " form-group-depend";
        }

        $html .= '">';

        if ($this->label !== null) {
            $html .= '<div class="form-group-header"><span class="form-label">'.htmlentities($this->label).'</span>';

            if (! $this->required && ! $this->disabled) {
                $html .= '<span class="form-label-addition"> (optional)</span>';
            }

            if (isset($this->description)) {
                $html .= '<p class="form-group-description">'.htmlentities($this->description)."</p>";
            }

            $html .= "</div>";
        }

        $html .= '<div class="form-group-body">'.
            '<div class="box">'.
            '<div class="box-row box-row-header box-row-tabnav"><div class="tabnav"><div class="tabnav-tabs">'.
            '<a class="tabnav-tab selected" href="#" data-tab-body="#js-markdown-field-'.htmlentities($this->name).'" tabindex="-1">Eingabe</a>'.
            '<a class="tabnav-tab js-markdown-tab" href="#" data-tab-body="#js-markdown-preview-'.htmlentities($this->name).'" data-markdown-source="#js-markdown-field-'.htmlentities($this->name).'" tabindex="-1">Vorschau</a>'.
            '<a class="tabnav-tab" href="#" data-tab-body="#js-markdown-help-'.htmlentities($this->name).'" tabindex="-1">Hilfe</a>'.
            '</div></div></div>'.
            '<div class="box-row">'.
            $this->generateField().
            '<div id="js-markdown-preview-'.htmlentities($this->name).'" class="markdown"></div>'.
            '<div id="js-markdown-help-'.htmlentities($this->name).'" class="markdown" style="display: none">'.
            '<p>Die Eingabe kann mit <i>Markdown</i> formatiert werden:</p>'.
            '<pre>Zwischen zwei Absätzen muss eine Zeile frei bleiben:'.PHP_EOL.PHP_EOL.
            'Dies ist der nächste Absatz.'.PHP_EOL.
            'Dieser Satz gehört noch zum selben Absatz, beginnt aber in einer neuen Zeile.'.PHP_EOL.PHP_EOL.
            '# Überschrift 1'.PHP_EOL.PHP_EOL.'## Überschrift 2'.PHP_EOL.PHP_EOL.'### Überschrift 3'.PHP_EOL.PHP_EOL.
            'Unnummerierte Liste:'.PHP_EOL.PHP_EOL.
            '* Diese Wörter sind fett: **Äpfel** und __Birnen__'.PHP_EOL.
            '* Dieser Wörter sind kursiv: *Katze* und _Maus_'.PHP_EOL.
            '* In diesem Satz sind ~~zwei Wörter~~ durchgestrichen.'.PHP_EOL.PHP_EOL.
            'Nummerierte Liste – die Zahlen werden automatisch durchnummeriert:'.PHP_EOL.PHP_EOL.
            '1. Code im Absatz kann so formatiert werden: `print(String)`'.PHP_EOL.
            '1. Links werden mit Protokoll automatisch erkannt: https://de.wikipedia.org/'.PHP_EOL.
            '1. Der Link kann aber auch durch Text ersetzt werden: [Wikipedia](https://de.wikipedia.org)'.PHP_EOL.PHP_EOL.
            'Hier folgt eine horizontale Linie mit voller Breite:'.PHP_EOL.PHP_EOL.
            '---'.PHP_EOL.PHP_EOL.
            'Mehrzeiliges Zitat:'.PHP_EOL.PHP_EOL.
            '> § 984'.PHP_EOL.
            '>'.PHP_EOL.
            '> Wird eine Sache, die so lange verborgen gelegen hat, dass der Eigentümer nicht mehr zu ermitteln ist (Schatz), entdeckt und infolge der Entdeckung in Besitz genommen, so wird das Eigentum zur Hälfte von dem Entdecker, zur Hälfte von dem Eigentümer der Sache erworben, in welcher der Schatz verborgen war.'.PHP_EOL.PHP_EOL.
            'Mehrzeiliger Code (formatiert wie diese Syntax-Hilfe):'.PHP_EOL.PHP_EOL.
            '```'.PHP_EOL.
            'public static void main(String[] args) {'.PHP_EOL.
            '    System.out.println("Hello, world!");'.PHP_EOL.
            '}'.PHP_EOL.
            '```'.PHP_EOL.
            '</pre></div>'.
            '</div></div>';

        foreach ($errorMessages as $errorMessage) {
            $html .= '<p class="form-error">'.htmlentities($errorMessage).'</p>';
        }

        $html .= '</div></div>';

        return $html;
    }
}
