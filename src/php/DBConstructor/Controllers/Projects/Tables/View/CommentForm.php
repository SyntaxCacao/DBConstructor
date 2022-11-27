<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\Projects\Tables\View;

use DBConstructor\Application;
use DBConstructor\Forms\Fields\MarkdownField;
use DBConstructor\Forms\Form;
use DBConstructor\Models\Row;

class CommentForm extends Form
{
    /** @var Row */
    public $row;

    public function __construct()
    {
        parent::__construct("table-view-comment");
    }

    public function init(Row &$row)
    {
        $this->row = &$row;

        $this->buttonLabel = "Kommentieren";

        $field = new MarkdownField("comment");
        $field->larger = false;
        $field->maxLength = Row::MAX_COMMENT_LENGTH;

        $this->addField($field);
    }

    public function perform(array $data)
    {
        $this->row->comment(Application::$instance->user->id, false, $data["comment"]);
    }
}
