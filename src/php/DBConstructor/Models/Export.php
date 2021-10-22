<?php

declare(strict_types=1);

namespace DBConstructor\Models;

use DBConstructor\SQL\MySQLConnection;

class Export
{
    const FORMAT_CSV = "csv";

    const FORMATS = [
        Export::FORMAT_CSV => "CSV"/*"CSV (Comma-separated values)"*/
    ];

    public static function create(string $projectId, string $userId, string $format, string $note = null): string
    {
        MySQLConnection::$instance->execute("INSERT INTO `dbc_export` (`project_id`, `user_id`, `format`, `note`) VALUES (?, ?, ?, ?)", [$projectId, $userId, $format, $note]);

        return MySQLConnection::$instance->getLastInsertId();
    }

    /**
     * @return Export|null
     */
    public static function load(string $id)
    {
        MySQLConnection::$instance->execute("SELECT e.*, p.`label` AS `project_label`, u.`firstname` AS `user_firstname`, u.`lastname` AS `user_lastname` FROM `dbc_export` e LEFT JOIN `dbc_project` p ON e.`project_id`=p.`id` LEFT JOIN `dbc_user` u ON e.`user_id` = u.`id`  WHERE e.`id`=?", [$id]);
        $result = MySQLConnection::$instance->getSelectedRows();

        if (count($result) != 1) {
            return null;
        }

        return new Export($result[0]);
    }

    /**
     * @return array<Export>
     */
    public static function loadList(string $projectId): array
    {
        MySQLConnection::$instance->execute("SELECT e.*, p.`label` AS `project_label`, u.`firstname` AS `user_firstname`, u.`lastname` AS `user_lastname` FROM `dbc_export` e LEFT JOIN `dbc_project` p ON e.`project_id`=p.`id` LEFT JOIN `dbc_user` u ON e.`user_id` = u.`id`  WHERE e.`project_id`=? ORDER BY e.`created` DESC", [$projectId]);
        $result = MySQLConnection::$instance->getSelectedRows();
        $list = [];

        foreach ($result as $row) {
            $list[] = new Export($row);
        }

        return $list;
    }

    public static function sanitizeProjectLabel(string $projectLabel): string
    {
        // Lowercase
        $projectLabel = strtolower($projectLabel);

        // Simplify special letters
        $projectLabel = preg_replace("/ä/", "ae", $projectLabel);
        $projectLabel = preg_replace("/ö/", "oe", $projectLabel);
        $projectLabel = preg_replace("/ü/", "ue", $projectLabel);
        $projectLabel = preg_replace("/ß/", "ss", $projectLabel);

        // Remove special characters
        $projectLabel = preg_replace("/[^a-z0-9]+/", "-", $projectLabel);

        // Remove trailing dashes
        $projectLabel = preg_replace("/^-+/", "", $projectLabel);
        $projectLabel = preg_replace("/-+$/", "", $projectLabel);

        return $projectLabel;
    }

    /** @var string */
    public $id;

    /** @var string */
    public $projectId;

    /** @var string */
    public $projectLabel;

    /** @var string */
    public $userId;

    /** @var string */
    public $userFirstName;

    /** @var string */
    public $userLastName;

    /** @var string */
    public $format;

    /** @var string|null */
    public $note;

    /** @var bool */
    public $deleted;

    /** @var string */
    public $created;

    /**
     * @param array<string, string> $data
     */
    public function __construct(array $data)
    {
        $this->id = $data["id"];
        $this->projectId = $data["project_id"];
        $this->projectLabel = $data["project_label"];
        $this->userId = $data["user_id"];
        $this->userFirstName = $data["user_firstname"];
        $this->userLastName = $data["user_lastname"];
        $this->format = $data["format"];
        $this->note = $data["note"];
        $this->deleted = $data["deleted"] == "1";
        $this->created = $data["created"];
    }

    public function getFileName(): string
    {
        return Export::sanitizeProjectLabel($this->projectLabel)."-export-".$this->id;
    }

    public function getFormatLabel(): string
    {
        return Export::FORMATS[$this->format];
    }
}
