<?php

declare(strict_types=1);

namespace DBConstructor\Forms\Fields;

class TextField extends GroupableField
{
    /** @var int|null */
    public $maxLength;

    /** @var int|null */
    public $minLength;

    /** @var bool */
    public $monospace = false;

    /**
     * To be overriden by child-classes.
     *
     * @var string
     */
    protected $type = "text";

    public function __construct(string $name, string $label = null)
    {
        parent::__construct($name, $label);
    }

    public function generateField(bool $placeholderLabel = false): string
    {
        $html = '<input class="form-input';

        if ($this->monospace) {
            $html .= ' form-input-monospace';
        }

        $html .= '" type="'.$this->type.'" name="field-'.htmlentities($this->name).'"';

        if (isset($this->dependsOn)) {
            $html .= ' data-depends-on="'.$this->dependsOn.'" data-depends-on-value="'.$this->dependsOnValue.'"';
        }

        if ($placeholderLabel) {
            $html .= ' placeholder="'.htmlentities($this->label).'"';
        }

        if (isset($this->maxLength)) {
            $html .= ' maxlength="'.$this->maxLength.'"';
        }

        if (isset($this->minLength)) {
            $html .= ' minlength="'.$this->minLength.'"';
        }

        if ($this->hasValue()) {
            $html .= ' value="'.htmlentities($this->value).'"';
        }

        if ($this->required && ! isset($this->dependsOn)) {
            $html .= ' required';
        }

        if ($this->disabled) {
            $html .= ' disabled';
        }

        $html .= ">";
        return $html;
    }

    public function validate(): array
    {
        $issues = [];

        if (! is_string($this->value)) {
            $issues[] = "Geben Sie eine Zeichenkette ein.";
            return $issues;
        }

        if (isset($this->maxLength) && strlen($this->value) > $this->maxLength) {
            $issues[] = "Geben Sie höchstens $this->maxLength Zeichen ein.";
        }

        if (isset($this->minLength) && strlen($this->value) < $this->minLength) {
            $issues[] = "Geben Sie wenigstens $this->minLength Zeichen ein.";
        }

        $this->callClosures($issues);
        /*
        foreach ($this->validationClosures as $validationClosure) {
            $closure = $validationClosure->closure;
            if (! $closure($this->value)) {
                $issues[] = $validationClosure->errorMessage;

                if ($validationClosure->break) {
                    break;
                }
            }
        }
        */

        return $issues;
    }
}
