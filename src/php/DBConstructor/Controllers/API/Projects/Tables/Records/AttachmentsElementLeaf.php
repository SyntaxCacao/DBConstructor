<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\API\Projects\Tables\Records;

use DBConstructor\Application;
use DBConstructor\Controllers\API\APIController;
use DBConstructor\Controllers\API\ForbiddenException;
use DBConstructor\Controllers\API\LeafNode;
use DBConstructor\Controllers\API\NotFoundException;
use DBConstructor\Controllers\API\Projects\ProjectsNode;
use DBConstructor\Controllers\API\Projects\Tables\TablesNode;
use DBConstructor\Controllers\API\UnprocessableException;
use DBConstructor\Models\AccessToken;
use DBConstructor\Models\RowAttachment;
use Exception;

class AttachmentsElementLeaf extends LeafNode
{
    const UPLOAD_ERROR_MESSAGES = [
        RowAttachment::UPLOAD_ERROR_FILE_TOO_LARGE => "The size of the file provided is larger than the server is configured to accept.",
        RowAttachment::UPLOAD_ERROR_GENERIC => "File could not be processed.",
        RowAttachment::UPLOAD_ERROR_NAME_INVALID_CHARS => "The file name provided contains illegal characters, allowed are A-Z, a-z, 0-9, dash, underscore, space and dot.",
        RowAttachment::UPLOAD_ERROR_NAME_TAKEN => "A file with the name you provided already exists.",
        RowAttachment::UPLOAD_ERROR_NAME_TOO_LONG => "The file name provided is too long, allowed are up to 70 characters.",
        RowAttachment::UPLOAD_ERROR_NO_FILE => "No file was received. Client also might have tried to send a file that was too large to process."
    ];

    public function delete(array $path): array
    {
        APIController::$instance->requireScope(ProjectsNode::$project->id, AccessToken::SCOPE_PROJECT_UPLOAD);

        if (strlen($path[8]) > RowAttachment::MAX_NAME_LENGTH ||
            ($attachment = RowAttachment::loadFromName(RecordsNode::$record->id, $path[8])) === null) {
            throw new NotFoundException();
        }

        if (! ProjectsNode::$participant->isManager &&
            $attachment->uploaderId !== Application::$instance->user->id) {
            throw new ForbiddenException("You may only delete attachments you created yourself as you are not a manager in this project.");
        }

        if (! unlink(RowAttachment::getPath(ProjectsNode::$project->id, TablesNode::$table->id, $attachment->rowId, $attachment->id))) {
            throw new Exception("unlink() returned false when trying to delete attachment with ID $attachment->id.");
        }

        RowAttachment::delete($attachment->id);

        return [];
    }

    public function get(array $path): array
    {
        if (strlen($path[8]) > RowAttachment::MAX_NAME_LENGTH ||
            ($attachment = RowAttachment::loadFromName(RecordsNode::$record->id, $path[8])) === null) {
            throw new NotFoundException();
        }

        $path = RowAttachment::getPath(ProjectsNode::$project->id, TablesNode::$table->id, RecordsNode::$record->id, $attachment->id);
        $attachment->checkPath($path);

        header("Content-Description: File Transfer");
        header("Content-Disposition: inline; filename=\"$attachment->fileName\"");
        header("Content-Length: ".filesize($path));

        readfile($path);
        exit;
    }

    public function post(array $path): array
    {
        APIController::$instance->requireScope(ProjectsNode::$project->id, AccessToken::SCOPE_PROJECT_UPLOAD);

        $params = $this->processQueryParameters([
            "overwrite" => [
                "type" => "boolean",
                "default" => false
            ]
        ]);

        // $path is always in lower case, using original path to retain capital letters
        $originalPath = explode("/", rtrim($_GET["path"], "/"));
        $fileName = $originalPath[count($originalPath) - 1];

        if ($params["overwrite"]) {
            if (ProjectsNode::$participant->isManager) {
                $overwrite = RowAttachment::UPLOAD_OVERWRITE_ANY;
            } else {
                $overwrite = RowAttachment::UPLOAD_OVERWRITE_OWN;
            }
        } else {
            $overwrite = RowAttachment::UPLOAD_OVERWRITE_NONE;
        }

        $result = RowAttachment::handleUpload(ProjectsNode::$project->id, RecordsNode::$record, $fileName, $overwrite);

        if ($result !== RowAttachment::UPLOAD_OK) {
            if ($params["overwrite"] && $result === RowAttachment::UPLOAD_ERROR_NAME_TAKEN) {
                throw new ForbiddenException("A file with the name you provided already exists and you are not permitted to overwrite it.");
            }

            throw new UnprocessableException(self::UPLOAD_ERROR_MESSAGES[$result]);
        }

        return [];
    }
}
