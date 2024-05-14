<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\Projects\Tables\View;

use DBConstructor\Application;
use DBConstructor\Controllers\Projects\ProjectsController;
use DBConstructor\Forms\Fields\MarkdownField;
use DBConstructor\Forms\Form;
use DBConstructor\Models\Row;
use DBConstructor\Models\RowAction;

class CommentEditForm extends Form
{
    /** @var RowAction */
    public $action;

    /** @var string */
    public $tableId;

    public function __construct()
    {
        parent::__construct("comment-edit");
    }

    public function init(RowAction $action, string $tableId)
    {
        $this->action = $action;
        $this->tableId = $tableId;

        $field = new MarkdownField("text");
        $field->defaultValue = $this->action->data;
        $field->larger = false;
        $field->maxLength = Row::MAX_COMMENT_LENGTH;

        $this->addField($field);
    }

    public function perform(array $data)
    {
        $this->action->edit($data["text"]);
        Application::$instance->redirect("projects/".ProjectsController::$projectId."/tables/$this->tableId/view/{$this->action->rowId}", "", "comment-{$this->action->id}");
        exit;
    }
}
