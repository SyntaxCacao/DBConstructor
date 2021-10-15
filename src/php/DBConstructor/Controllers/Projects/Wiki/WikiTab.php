<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\Projects\Wiki;

use DBConstructor\Controllers\ComingSoonController;
use DBConstructor\Controllers\NotFoundController;
use DBConstructor\Controllers\TabController;
use DBConstructor\Models\Page;

class WikiTab extends TabController
{
    public function __construct()
    {
        parent::__construct("Wiki", "wiki", "book");
    }

    public function request(array $path, &$data): bool
    {
        if (count($path) == 3) {
            if (is_null($data["project"]->mainPageId)) {
                // show blank slate
                $data["tabpage"] = "blank";
            } else {
                // show main page
                $this->showPage(Page::load($data["project"]->mainPageId), $data);
            }

            return true;
        }

        if (count($path) == 4) {
            if ($path[3] == "create") {
                // create page
                $form = new PageForm();
                $form->init($data["project"], null, null, null);
                $form->process();

                $data["form"] = $form;
                $data["editMode"] = false;

                $data["tabpage"] = "form";
                $data["title"] = "Seite anlegen";
                return true;
            }
        }

        $page = Page::load($path[3]);

        if (is_null($page)) {
            (new NotFoundController())->request($path);
            return false;
        }

        if (count($path) == 4) {
            // show page
            $this->showPage($page, $data);
            return true;
        }

        if (count($path) == 5 && $path[4] == "edit") {
            // edit page
            $state = $page->loadCurrentState();

            $form = new PageForm();
            $form->init($data["project"], $page, $page->title, $state->text);
            $form->process();

            $data["form"] = $form;
            $data["editMode"] = true;

            $data["tabpage"] = "form";
            $data["title"] = "Seite bearbeiten";
            return true;
        }

        if (count($path) == 5 && $path[4] == "history") {
            // TODO show history
            (new ComingSoonController())->request($path);
            return false;
        }

        if (count($path) == 6 && $path[4] == "history") {
            // TODO search for state
        }

        (new NotFoundController())->request($path);
        return false;
    }

    public function showPage(Page $page, &$data)
    {
        if (isset($_REQUEST["moveDown"]) && is_numeric($_REQUEST["moveDown"])) {
            $pages = Page::loadList($data["project"]->id);
            $movePage = Page::load($_REQUEST["moveDown"]);

            if ($movePage->position < count($pages) && $movePage->projectId == $page->projectId) {
                $movePage->moveDown();
            }
        }

        if (isset($_REQUEST["moveUp"]) && is_numeric($_REQUEST["moveUp"])) {
            $movePage = Page::load($_REQUEST["moveUp"]);

            if ($movePage->position > 1 && $movePage->projectId == $page->projectId) {
                $movePage->moveUp();
            }
        }

        $pages = Page::loadList($data["project"]->id);
        $state = $page->loadCurrentState();

        $data["tabpage"] = "page";
        $data["title"] = $page->title;
        $data["wikiPage"] = $page; // $data["page"] is already in use
        $data["state"] = $state;
        $data["pages"] = $pages;
    }
}
