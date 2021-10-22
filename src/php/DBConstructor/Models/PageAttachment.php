<?php

declare(strict_types=1);

namespace DBConstructor\Models;

use DBConstructor\SQL\MySQLConnection;

class PageAttachment
{
    public static function create(string $pageId, User $uploader, string $fileName)
    {
        MySQLConnection::$instance->execute("INSERT INTO `dbc_page_attachment` (`page_id`, `uploader_id`, `filename`) VALUES (?, ?, ?)", [$pageId, $uploader->id, $fileName]);
    }

    /** @var string */
    public $id;

    /** @var string */
    public $uploaderId;

    /** @var string */
    public $uploaderFirstName;

    /** @var string */
    public $uploaderLastName;

    /** @var string */
    public $fileName;

    /** @var string */
    public $created;

    /**
     * @param array<string, string> $data
     */
    public function __construct(array $data)
    {
        $this->id = $data["id"];
        $this->uploaderId = $data["uploader_id"];
        $this->uploaderFirstName = $data["uploader_firstname"];
        $this->uploaderLastName = $data["uploader_lastname"];
        $this->fileName = $data["filename"];
        $this->created = $data["created"];
    }
}
