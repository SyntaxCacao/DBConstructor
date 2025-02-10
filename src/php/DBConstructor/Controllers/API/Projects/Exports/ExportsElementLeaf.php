<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\API\Projects\Exports;

use DBConstructor\Application;
use DBConstructor\Controllers\API\ForbiddenException;
use DBConstructor\Controllers\API\LeafNode;

class ExportsElementLeaf extends LeafNode
{
    public function delete(array $path): array
    {
        if (ExportsNode::$export->userId !== Application::$instance->user->id) {
            throw new ForbiddenException();
        }

        return [
            "success" => ExportsNode::$export->delete()
        ];
    }

    public function get(array $path): array
    {
        $result = [
            "id" => (int) ExportsNode::$export->id,
            "format" => ExportsNode::$export->format,
            "user" => [
                "id" => (int) ExportsNode::$export->userId,
                "firstName" => ExportsNode::$export->userFirstName,
                "lastName" => ExportsNode::$export->userLastName
            ],
            "created" => ExportsNode::$export->created,
            "archive" => ExportsNode::$export->getArchiveDownloadName(),
            "files" => []
        ];

        if (! ExportsNode::$export->existsLocalArchive()) {
            $result["archive"] = null;
        }

        if (ExportsNode::$export->existsLocalDirectory()) {
            $directory = ExportsNode::$export->getLocalDirectoryPath();
            $files = scandir($directory);

            foreach ($files as $file) {
                if ($file === "." || $file === "..") {
                    continue;
                }

                $result["files"][$file] = filesize("$directory/$file");
            }
        }

        return $result;
    }
}
