<?php

declare(strict_types=1);

namespace DBConstructor\Models;

use DBConstructor\SQL\MySQLConnection;

class RowAttachment
{
    public static function create(string $rowId, User $uploader, string $fileName, int $size): string
    {
        MySQLConnection::$instance->execute("INSERT INTO `dbc_row_attachment` (`row_id`, `uploader_id`, `filename`, `size`) VALUES (?, ?, ?, ?)", [$rowId, $uploader->id, $fileName, $size]);
        return MySQLConnection::$instance->getLastInsertId();
    }

    public static function delete(string $id)
    {
        MySQLConnection::$instance->execute("DELETE FROM `dbc_row_attachment` WHERE `id`=?", [$id]);
    }

    public static function getPath(string $projectId, string $tableId, string $rowId, string $attachmentId): string
    {
        return "../tmp/attachments/$projectId/tables/$tableId/$rowId/$attachmentId";
    }

    public static function isNameAvailable(string $rowId, string $fileName): bool
    {
        MySQLConnection::$instance->execute("SELECT IF(COUNT(*) > 0, FALSE, TRUE) AS `available` FROM `dbc_row_attachment` WHERE `row_id`=? AND `filename`=?", [$rowId, $fileName]);
        return intval(MySQLConnection::$instance->getSelectedRows()[0]["available"]) === 1;
    }

    /**
     * @return RowAttachment|null
     */
    public static function load(string $id)
    {
        MySQLConnection::$instance->execute("SELECT * FROM `dbc_row_attachment` WHERE `id`=?", [$id]);
        $result = MySQLConnection::$instance->getSelectedRows();

        if (count($result) !== 1) {
            return null;
        }

        return new RowAttachment($result[0]);
    }

    /**
     * @return array<string, RowAttachment>
     */
    public static function loadAll(string $rowId): array
    {
        MySQLConnection::$instance->execute("SELECT a.*, ".
            "u.`firstname` AS `uploader_firstname`, ".
            "u.`lastname` AS `uploader_lastname` ".
            "FROM `dbc_row_attachment` a ".
            "LEFT JOIN `dbc_user` u ON u.`id`=a.`uploader_id` ".
            "WHERE a.`row_id`=? ".
            "ORDER BY a.`filename`", [$rowId]);
        $result = MySQLConnection::$instance->getSelectedRows();
        $list = [];

        foreach ($result as $row) {
            $attachment = new RowAttachment($row);
            $list[$attachment->id] = new RowAttachment($row);
        }

        return $list;
    }

    /**
     * @return RowAttachment|null
     */
    public static function loadFromName(string $rowId, string $fileName)
    {
        MySQLConnection::$instance->execute("SELECT a.*, ".
            "u.`firstname` AS `uploader_firstname`, ".
            "u.`lastname` AS `uploader_lastname` ".
            "FROM `dbc_row_attachment` a ".
            "LEFT JOIN `dbc_user` u ON u.`id`=a.`uploader_id` ".
            "WHERE a.`row_id`=? AND a.`filename`=?", [$rowId, $fileName]);
        $result = MySQLConnection::$instance->getSelectedRows();

        if (count($result) !== 1) {
            return null;
        }

        return new RowAttachment($result[0]);
    }

    /** @var string */
    public $id;

    /** @var string */
    public $rowId;

    /** @var string */
    public $uploaderId;

    /** @var string */
    public $uploaderFirstName;

    /** @var string */
    public $uploaderLastName;

    /** @var string */
    public $fileName;

    /** @var int|float */
    public $size;

    /** @var string */
    public $created;

    /** @var string */
    public $type;

    /**
     * @param array<string, string> $data
     */
    public function __construct(array $data)
    {
        $this->id = $data["id"];
        $this->rowId = $data["row_id"];
        $this->uploaderId = $data["uploader_id"];
        $this->fileName = $data["filename"];
        $this->size = $data["size"];
        $this->created = $data["created"];
        $this->type = strtolower(pathinfo($this->fileName, PATHINFO_EXTENSION));

        if (isset($data["uploader_firstname"])) {
            $this->uploaderFirstName = $data["uploader_firstname"];
        }

        if (isset($data["uploader_lastname"])) {
            $this->uploaderLastName = $data["uploader_lastname"];
        }
    }

    public function getHumanFileSize(): string
    {
        // https://www.php.net/manual/en/function.filesize.php#106569
        // May return unexpected results on some file systems for file sizes of more than 2 GB

        $factor = floor((strlen((string) $this->size) - 1) / 3);
        $prefix = ["B", "KB", "MB", "GB", "TB", "PB"][$factor];
        // "1" is number of decimals shown
        return number_format($this->size / pow(1024, $factor), $prefix === "B" ? 0 : 2, ",", ".")." ".$prefix;
    }

    public function getTypeIcon(): string
    {
        // use match() when upgrading to PHP 8
        switch ($this->type) {
            case "bmp":
            case "gif":
            case "jpeg":
            case "jpg":
            case "png":
                return "file-earmark-image";
            case "doc":
            case "docx":
            case "odt":
            case "txt":
            case "md":
                return "file-earmark-text";
            case "pdf":
            case "rtf":
                return "file-earmark-richtext";
            case "ppt":
            case "pptx":
            case "odp":
                return "file-earmark-slides";
            case "xls":
            case "xlsx":
            case "ods":
            case "csv":
            case "tsv":
                return "file-earmark-spreadsheet";
            case "zip":
            case "tar":
            case "gz":
            case "bz2":
            case "rar":
                return "file-earmark-zip";
            case "html":
            case "json":
            case "xml":
            case "yml":
                return "file-earmark-code";
            default:
                return "file-earmark";
        }
    }

    /**
     * @return string|null
     */
    public function getViewWarning()
    {
        if ($this->type === "html") {
            return "Vorsicht: Rufen Sie nur HTML-Dateien von Personen auf, denen Sie vertrauen, da dort schädlicher Code enthalten sein kann.";
        }

        return null;
    }

    public function isViewable(): bool
    {
        switch ($this->type) {
            case "bmp":
            case "csv":
            case "gif":
            case "html":
            case "jpeg":
            case "jpg":
            case "md":
            case "pdf":
            case "png":
            case "txt":
            case "xml":
            case "yml":
                return true;
            default:
                return false;
        }
    }
}
