<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\API\Projects\Exports;

use DBConstructor\Controllers\API\APIController;
use DBConstructor\Controllers\API\InternalNode;
use DBConstructor\Controllers\API\MethodNotAllowedException;
use DBConstructor\Controllers\API\NotFoundException;
use DBConstructor\Controllers\API\Projects\ProjectsNode;
use DBConstructor\Controllers\Exports\ExportsController;
use DBConstructor\Models\AccessToken;
use DBConstructor\Models\Export;

class ExportsNode extends InternalNode
{
    /** @var Export */
    public static $export;

    public function process($path): array
    {
        APIController::$instance->requireScope(ProjectsNode::$project->id, AccessToken::SCOPE_PROJECT_EXPORT);

        if (count($path) === 4) {
            // List exports and perform export
            return (new ExportsListLeaf())->process($path);
        }

        if (intval($path[4]) === 0 || (self::$export = Export::load($path[4])) === null ||
            self::$export->projectId !== ProjectsNode::$project->id) {
            throw new NotFoundException();
        }

        if (count($path) === 5) {
            // Get export details
            return (new ExportsElementLeaf())->process($path);
        }

        if (count($path) === 6 && $path[5] === self::$export->getArchiveDownloadName() &&
            self::$export->existsLocalArchive()) {
            // Download archive
            if ($_SERVER["REQUEST_METHOD"] !== "GET") {
                throw new MethodNotAllowedException();
            }

            ExportsController::readFile(ExportsNode::$export->getLocalArchivePath(), ExportsNode::$export->getArchiveDownloadName());
            exit;
        }

        if (count($path) === 6 && ($fileName = self::$export->lookUpLocalFile($path[5])) !== null) {
            // Download single file
            if ($_SERVER["REQUEST_METHOD"] !== "GET") {
                throw new MethodNotAllowedException();
            }

            ExportsController::readFile(ExportsNode::$export->getLocalDirectoryPath()."/".$fileName, $fileName);
            exit;
        }

        throw new NotFoundException();
    }
}
