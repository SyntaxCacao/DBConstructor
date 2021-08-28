<?php

declare(strict_types=1);

namespace DBConstructor\Models;

use DBConstructor\SQL\MySQLConnection;

class Page
{
    /**
     * @param string|null $comment
     */
    public static function create(Project $project, User $creator, string $title, string $text, $comment): string
    {
        // Get next position
        MySQLConnection::$instance->execute("SELECT `position` FROM `dbc_page` WHERE `project_id`=? ORDER BY `position` DESC LIMIT 1", [$project->id]);

        $result = MySQLConnection::$instance->getSelectedRows();
        $position = 1;

        if (count($result) > 0) {
            $position = intval($result[0]["position"]) + 1;
        }

        // Insertion
        MySQLConnection::$instance->execute("INSERT INTO `dbc_page` (`project_id`, `title`, `position`) VALUES (?, ?, ?)", [$project->id, $title, $position]);
        $id = MySQLConnection::$instance->getLastInsertId();

        // Create PageState
        PageState::create($id, $creator, $title, $text, $comment);

        return $id;
    }

    /**
     * @return Page|null
     */
    public static function load(string $id)
    {
        MySQLConnection::$instance->execute("SELECT * FROM `dbc_page` WHERE `id`=?", [$id]);
        $result = MySQLConnection::$instance->getSelectedRows();

        if (count($result) != 1) {
            return null;
        }

        return new Page($result[0]);
    }

    /**
     * @return Page[]
     */
    public static function loadList(string $projectId): array
    {
        MySQLConnection::$instance->execute("SELECT * FROM `dbc_page` WHERE `project_id`=? ORDER BY `position`", [$projectId]);
        $result = MySQLConnection::$instance->getSelectedRows();
        $list = [];

        foreach ($result as $row) {
            $list[] = new Page($row);
        }

        return $list;
    }

    /** @var string */
    public $id;

    /** @var string */
    public $projectId;

    /** @var string */
    public $title;

    /** @var string */
    public $position;

    /** @var string */
    public $created;

    /**
     * @param string[] $data
     */
    public function __construct(array $data)
    {
        $this->id = $data["id"];
        $this->projectId = $data["project_id"];
        $this->title = $data["title"];
        $this->position = $data["position"];
        $this->created = $data["created"];
    }

    /**
     * @param string|null $comment
     */
    public function edit(User $editor, string $title, string $text, $comment)
    {
        PageState::create($this->id, $editor, $title, $text, $comment);
        MySQLConnection::$instance->execute("UPDATE `dbc_page` SET `title`=? WHERE `id`=?", [$title, $this->id]);
        $this->title = $title;
    }

    public function loadCurrentState(): PageState
    {
        return PageState::loadCurrent($this);
    }

    public function moveDown()
    {
        MySQLConnection::$instance->execute("UPDATE `dbc_page` SET `position`=`position`-1 WHERE `project_id`=? AND `position`=?+1", [$this->projectId, $this->position]);
        MySQLConnection::$instance->execute("UPDATE `dbc_page` SET `position`=`position`+1 WHERE `id`=?", [$this->id]);
    }

    public function moveUp()
    {
        MySQLConnection::$instance->execute("UPDATE `dbc_page` SET `position`=`position`+1 WHERE `project_id`=? AND `position`=?-1", [$this->projectId, $this->position]);
        MySQLConnection::$instance->execute("UPDATE `dbc_page` SET `position`=`position`-1 WHERE `id`=?", [$this->id]);
    }
}
