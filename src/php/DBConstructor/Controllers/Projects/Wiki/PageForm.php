<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\Projects\Wiki;

use DBConstructor\Application;
use DBConstructor\Forms\Fields\MarkdownField;
use DBConstructor\Forms\Fields\TextField;
use DBConstructor\Forms\Form;
use DBConstructor\Models\Page;
use DBConstructor\Models\Project;

class PageForm extends Form
{
    /** @var Project */
    public $project;

    /** @var Page|null */
    public $page;

    /** @var string|null */
    public $title;

    /** @var string|null */
    public $text;

    public function __construct()
    {
        parent::__construct("wikipage");
    }

    /**
     * @param $page Page|null null on creation
     * @param $title string|null null on creation
     * @param $text string|null null on creation
     */
    public function init(Project $project, $page, $title, $text)
    {
        $this->project = $project;
        $this->page = $page;

        // title
        $field = new TextField("title", "Titel");
        $field->maxLength = 100;

        if (! is_null($title)) {
            $field->defaultValue = $title;
        }

        $this->addField($field);

        // text
        $field = new MarkdownField("text", "Text");
        $field->maxLength = 64000;

        if (! is_null($text)) {
            $field->defaultValue = $text;
        }

        $this->addField($field);

        // comment
        $field = new TextField("comment", "Bemerkung");
        $field->description = "Kurze Beschreibung der Änderung";
        $field->expand = true;
        $field->maxLength = 1000;
        $field->required = false;

        $this->addField($field);
    }

    public function perform(array $data)
    {
        if (is_null($this->page)) {
            $id = Page::create($this->project, Application::$instance->user, $data["title"], $data["text"], $data["comment"]);

            if (is_null($this->project->mainPageId)) {
                $this->project->setMainPage($id);
            }

            Application::$instance->redirect("projects/".$this->project->id."/wiki/".$id, "saved");
        } else {
            $this->page->edit(Application::$instance->user, $data["title"], $data["text"], $data["comment"]);
            Application::$instance->redirect("projects/".$this->project->id."/wiki/".$this->page->id, "saved");
        }
    }
}
