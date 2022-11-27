<?php

declare(strict_types=1);

namespace DBConstructor\Models;

use DBConstructor\Application;
use DBConstructor\SQL\MySQLConnection;
use Exception;

class RowAttachment
{
    const MAX_NAME_LENGTH = 70;

    const UPLOAD_ERROR_FILE_TOO_LARGE = 1;

    const UPLOAD_ERROR_GENERIC = 2;

    const UPLOAD_ERROR_NAME_INVALID_CHARS = 3;

    const UPLOAD_ERROR_NAME_TAKEN = 4;

    const UPLOAD_ERROR_NAME_TOO_LONG = 5;

    const UPLOAD_ERROR_NO_FILE = 6;

    const UPLOAD_OK = 0;

    const UPLOAD_OVERWRITE_ANY = 2;

    const UPLOAD_OVERWRITE_NONE = 0;

    const UPLOAD_OVERWRITE_OWN = 1;

    public static function create(string $rowId, User $uploader, string $fileName, int $size): string
    {
        MySQLConnection::$instance->execute("INSERT INTO `dbc_row_attachment` (`row_id`, `uploader_id`, `filename`, `size`) VALUES (?, ?, ?, ?)", [$rowId, $uploader->id, $fileName, $size]);
        return MySQLConnection::$instance->getLastInsertId();
    }

    public static function delete(string $id)
    {
        MySQLConnection::$instance->execute("DELETE FROM `dbc_row_attachment` WHERE `id`=?", [$id]);
    }

    /**
     * @throws Exception
     */
    public static function deleteRow(string $projectId, string $tableId, string $rowId)
    {
        $attachments = RowAttachment::loadAll($rowId);

        foreach ($attachments as $attachment) {
            if (! unlink(RowAttachment::getPath($projectId, $tableId, $rowId, $attachment->id))) {
                throw new Exception("unlink() returned false when trying to delete attachment with ID $attachment->id.");
            }

            RowAttachment::delete($attachment->id);
        }
    }

    public static function getPath(string $projectId, string $tableId, string $rowId, string $attachmentId): string
    {
        return "../tmp/attachments/$projectId/tables/$tableId/$rowId/$attachmentId";
    }

    /**
     * @throws Exception
     */
    public static function handleUpload(string $projectId, Row $row, string $fileName = null, int $overwritePolicy = RowAttachment::UPLOAD_OVERWRITE_NONE): int
    {
        if (! isset($_FILES["file"]) || ! isset($_FILES["file"]["name"]) || ! is_string($_FILES["file"]["name"]) || ! isset($_FILES["file"]["error"]) || ! isset($_FILES["file"]["tmp_name"])) {
            // $_FILES["file"] won't be set if POST Content-Length is too large
            // $_FILES["file"]["name"] won't be string if multiple files are sent
            return RowAttachment::UPLOAD_ERROR_NO_FILE;
        }

        if ($fileName === null) {
            $fileName = $_FILES["file"]["name"];
        }

        $error = $_FILES["file"]["error"];

        if ($error !== UPLOAD_ERR_OK) {
            if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
                return RowAttachment::UPLOAD_ERROR_FILE_TOO_LARGE;
            } else {
                return RowAttachment::UPLOAD_ERROR_GENERIC;
            }
        }

        if (strlen($fileName) > RowAttachment::MAX_NAME_LENGTH) {
            return RowAttachment::UPLOAD_ERROR_NAME_TOO_LONG;
        }

        if (! preg_match("/^[a-zA-Z0-9_\-. ]+$/", $fileName)) {
            return RowAttachment::UPLOAD_ERROR_NAME_INVALID_CHARS;
        }

        if ($overwritePolicy === RowAttachment::UPLOAD_OVERWRITE_NONE) {
            if (! RowAttachment::isNameAvailable($row->id, $fileName)) {
                return RowAttachment::UPLOAD_ERROR_NAME_TAKEN;
            }
        } else if (($attachment = RowAttachment::loadFromName($row->id, $fileName)) !== null) {
            if ($overwritePolicy === RowAttachment::UPLOAD_OVERWRITE_OWN && $attachment->uploaderId !== Application::$instance->user->id) {
                return RowAttachment::UPLOAD_ERROR_NAME_TAKEN;
            }

            if (! unlink(RowAttachment::getPath($projectId, $row->tableId, $row->id, $attachment->id))) {
                throw new Exception("unlink() returned false when trying to delete attachment with ID $attachment->id.");
            }

            $attachment->replace(Application::$instance->user, $fileName, filesize($_FILES["file"]["tmp_name"]));
            $attachmentId = $attachment->id;
        }

        Application::$instance->checkDir("tmp/attachments/$projectId/");
        Application::$instance->checkDir("tmp/attachments/$projectId/tables/");
        Application::$instance->checkDir("tmp/attachments/$projectId/tables/$row->tableId/");
        Application::$instance->checkDir("tmp/attachments/$projectId/tables/$row->tableId/$row->id");

        if (! isset($attachmentId)) {
            $attachmentId = RowAttachment::create($row->id, Application::$instance->user, $fileName, filesize($_FILES["file"]["tmp_name"]));
        }

        if (! move_uploaded_file($_FILES["file"]["tmp_name"], RowAttachment::getPath($projectId, $row->tableId, $row->id, $attachmentId))) {
            RowAttachment::delete($attachmentId);
            throw new Exception("move_uploaded_file() returned false for upload of file named \"$fileName\" initiated by user with ID ".Application::$instance->user->id);
        }

        return RowAttachment::UPLOAD_OK;
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

    /**
     * @throws Exception If file does not exist or is not readable
     */
    public function checkPath(string $path)
    {
        if (! file_exists($path)) {
            throw new Exception("Download file for attachment with ID $this->id not found (expected path: \"$path\")");
        }

        if (! is_readable($path)) {
            throw new Exception("Download file for attachment with ID $this->id is not readable (expected path: \"$path\")");
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

    public function replace(User $uploader, string $fileName, int $size)
    {
        MySQLConnection::$instance->execute("UPDATE `dbc_row_attachment` SET `uploader_id`=?, `filename`=?, `size`=?, `created`=CURRENT_TIMESTAMP WHERE `id`=?", [$uploader->id, $fileName, $size, $this->id]);
    }
}
