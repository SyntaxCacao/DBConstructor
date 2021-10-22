<?php

declare(strict_types=1);

namespace DBConstructor\Forms\Fields;

abstract class GroupableField extends Field
{
    /** @var bool */
    public $expand = false;

    /** @var string|null */
    public $footer;

    /**
     * @param array<string> $errorMessages
     */
    public function generateGroup(array $errorMessages = []): string
    {
        $html = '<label class="form-group';

        if (isset($this->dependsOn)) {
            $html .= ' form-group-depend';
        }

        if ($this->expand) {
            $html .= ' form-block';
        }

        $html .= '"><div class="form-group-header"><span class="form-label">'.htmlentities($this->label).'</span>';

        if (! $this->required && ! $this->disabled) {
            $html .= '<span class="form-label-addition"> (optional)</span>';
        }

        if (isset($this->description)) {
            $html .= '<p class="form-group-description">'.htmlentities($this->description).'</p>';
        }

        $html .= '</div><div class="form-group-body">'.$this->generateField();

        if (isset($this->footer)) {
            $html .= '<p class="form-footer">'.htmlentities($this->footer).'</p>';
        }

        foreach ($errorMessages as $errorMessage) {
            $html .= '<p class="form-error">'.htmlentities($errorMessage).'</p>';
        }

        $html .= '</div></label>';
        return $html;
    }
}
