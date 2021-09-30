<?php

declare(strict_types=1);

namespace DBConstructor\Forms\Fields;

class ListFieldColumn
{
    /** @var int|null */
    public $maxLength;

    /** @var bool */
    public $monospace = false;

    /** @var string */
    public $name;

    /** @var string|null */
    public $placeholder;

    /** @var bool */
    public $required = true;

    public function __construct(string $name, string $placeholder = null)
    {
        $this->name = $name;
        $this->placeholder = $placeholder;
    }

    public function generateField(string $listName, int $rowNumber, string $value = null): string
    {
        $html = '<input class="form-input';

        if ($this->monospace) {
            $html .= " form-input-monospace";
        }

        $html .= '" type="text" name="field-'.htmlentities($listName)."-".$rowNumber."-".htmlentities($this->name).'"';

        if (! is_null($this->placeholder)) {
            $html .= ' placeholder="'.htmlentities($this->placeholder).'"';
        }

        if (! is_null($this->maxLength)) {
            $html .= ' maxlength="'.$this->maxLength.'"';
        }

        $html .= ' data-column-name="'.htmlentities($this->name).'"';

        if (! is_null($value)) {
            $html .= ' value="'.htmlentities($value).'"';
        }

        $html .= ">";
        return $html;
    }
}
