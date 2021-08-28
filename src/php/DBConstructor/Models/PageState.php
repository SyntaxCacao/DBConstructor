<?php

declare(strict_types=1);

namespace DBConstructor\Models;

use DBConstructor\SQL\MySQLConnection;

class PageState
{
    /**
     * @param string|null $comment
     */
    public static function create(string $pageId, User $creator, string $title, string $text, $comment)
    {
        MySQLConnection::$instance->execute("INSERT INTO `dbc_page_state` (`page_id`, `creator_id`, `title`, `text`, `comment`) VALUES (?, ?, ?, ?, ?)", [$pageId, $creator->id, $title, $text, $comment]);
    }

    /**
     * @return PageState|null
     */
    public static function load(string $id)
    {
        MySQLConnection::$instance->execute("SELECT s.*, u.`firstname` AS creator_firstname, u.`lastname` AS creator_lastname FROM `dbc_page_state` s LEFT JOIN `dbc_user` u ON s.`creator_id` = u.`id` WHERE s.`id`=?", [$id]);
        $result = MySQLConnection::$instance->getSelectedRows();

        if (count($result) != 1) {
            return null;
        }

        return new PageState($result[0]);
    }

    public static function loadCurrent(Page $page): PageState
    {
        MySQLConnection::$instance->execute("SELECT s.*, u.`firstname` AS creator_firstname, u.`lastname` AS creator_lastname FROM `dbc_page_state` s LEFT JOIN `dbc_user` u ON s.`creator_id` = u.`id` WHERE s.`page_id`=? ORDER BY s.`created` DESC LIMIT 1", [$page->id]);
        $result = MySQLConnection::$instance->getSelectedRows();
        return new PageState($result[0]);
    }

    /**
     * @return PageState[]
     */
    public static function loadList(string $pageId): array
    {
        MySQLConnection::$instance->execute("SELECT s.*, u.`firstname` AS `creator_firstname`, u.`lastname` AS `creator_lastname` FROM `dbc_page_state` s LEFT JOIN `dbc_user` u ON s.`creator_id` = u.`id` WHERE s.`page_id`=? ORDER BY s.`created`", [$pageId]);
        $result = MySQLConnection::$instance->getSelectedRows();
        $list = [];

        foreach ($result as $row) {
            $list[] = new PageState($row);
        }

        return $list;
    }

    /** @var string */
    public $id;

    /** @var string */
    public $creatorId;

    /** @var string */
    public $creatorFirstName;

    /** @var string */
    public $creatorLastName;

    /** @var string */
    public $title;

    /** @var string */
    public $text;

    /** @var string */
    public $comment;

    /** @var string */
    public $created;

    /**
     * @param string[] $data
     */
    public function __construct(array $data)
    {
        $this->id = $data["id"];
        $this->creatorId = $data["creator_id"];
        $this->creatorFirstName = $data["creator_firstname"];
        $this->creatorLastName = $data["creator_lastname"];
        $this->title = $data["title"];
        $this->text = $data["text"];
        $this->comment = $data["comment"];
        $this->created = $data["created"];
    }
}
