<?php

declare(strict_types=1);

namespace DBConstructor\Models;

use DBConstructor\SQL\MySQLConnection;
use DBConstructor\Util\StringSanitizer;

class Export
{
    const FORMAT_CSV = "csv";

    const FORMATS = [
        Export::FORMAT_CSV => "CSV"/*"CSV (Comma-separated values)"*/
    ];

    const TMP_DIR_EXPORTS = "../tmp/exports";

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
        $this->created = $data["created"];
    }

    public function delete(): bool
    {
        $dir = $this->getLocalDirectoryPath();

        if (file_exists($dir)) {
            if (! is_dir($dir)) {
                return false;
            }

            $files = scandir($dir);

            foreach ($files as $file) {
                if ($file === "." || $file === "..") {
                    continue;
                }

                $file = "$dir/$file";

                if (! is_file($file) || ! unlink($file)) {
                    return false;
                }
            }

            if (! rmdir($dir)) {
                return false;
            }
        }

        $archive = $this->getLocalArchivePath();

        if (file_exists($archive)) {
            if (! unlink($archive)) {
                return false;
            }
        }

        MySQLConnection::$instance->execute("DELETE FROM `dbc_export` WHERE `id`=?", [$this->id]);
        return true;
    }

    public function existsLocalArchive(): bool
    {
        $path = $this->getLocalArchivePath();
        return file_exists($path) && is_readable($path);
    }

    public function existsLocalDirectory(): bool
    {
        $path = $this->getLocalDirectoryPath();
        return file_exists($path) && is_dir($path) && is_readable($path);
    }

    public function getArchiveDownloadName(): string
    {
        return StringSanitizer::toFileName($this->projectLabel)."-export-".$this->id;
    }

    public function getFormatLabel(): string
    {
        return Export::FORMATS[$this->format];
    }

    public function getLocalArchivePath(): string
    {
        return Export::TMP_DIR_EXPORTS."/export-$this->id.zip";
    }

    public function getLocalDirectoryPath(): string
    {
        return Export::TMP_DIR_EXPORTS."/export-$this->id";
    }

    /**
     * @return null|string Real-case file name if found, null if not found
     */
    public function lookUpLocalFile(string $search)
    {
        $dir = $this->getLocalDirectoryPath();
        $files = scandir($dir);

        foreach ($files as $file) {
            if ($file === "." || $file === "..") {
                continue;
            }

            if (strtolower($search) === strtolower($file) && is_file("$dir/$file") && is_readable("$dir/$file")) {
                return $file;
            }
        }

        return null;
    }
}
