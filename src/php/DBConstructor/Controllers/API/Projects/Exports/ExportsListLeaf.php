<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\API\Projects\Exports;

use DBConstructor\Controllers\API\LeafNode;
use DBConstructor\Controllers\API\Projects\ProjectsNode;
use DBConstructor\Controllers\Projects\Exports\ExportProcess;
use DBConstructor\Models\Export;

class ExportsListLeaf extends LeafNode
{
    const OPTIONS_COMMENTS_FORMAT = [
        "json" => ExportProcess::COMMENTS_FORMAT_JSON,
        "text" => ExportProcess::COMMENTS_FORMAT_TEXT
    ];

    public function get(array $path): array
    {
        $exports = Export::loadList(ProjectsNode::$project->id);
        $result = [];

        foreach ($exports as $export) {
            $result[] = [
                "id" => (int) $export->id,
                "format" => $export->format,
                "user" => [
                    "id" => (int) $export->userId,
                    "firstName" => $export->userFirstName,
                    "lastName" => $export->userLastName
                ],
                "created" => $export->created
            ];
        }

        return $result;
    }

    public function post(array $path): array
    {
        $params = $this->processPayloadParameters([
            "includeInternalIds" => [
                "type" => "boolean",
                "default" => false
            ],
            "internalIdColumnName" => [
                "type" => "string",
                "default" => "_intid",
            ],
            "includeComments" => [
                "type" => "boolean",
                "default" => false
            ],
            "commentsColumnName" => [
                "type" => "string",
                "default" => null
            ],
            "commentsFormat" => [
                "type" => "options",
                "default" => "json",
                "options" => self::OPTIONS_COMMENTS_FORMAT
            ],
            "commentsAnonymize" => [
                "type" => "boolean",
                "default" => false
            ],
            "commentsExcludeAPI" => [
                "type" => "boolean",
                "default" => true
            ],
            "generateSchemeDocs" => [
                "type" => "boolean",
                "default" => true
            ],
            "note" => [
                "type" => "string",
                "default" => null
            ]
        ]);

        $process = new ExportProcess(ProjectsNode::$project);
        $process->api = true;
        $process->includeInternalIds = $params["includeInternalIds"];
        // TODO: Prüfungen, wie sie im ExportForm erfolgen
        $process->internalIdColumnName = $params["internalIdColumnName"];
        $process->includeComments = $params["includeComments"];

        if ($params["commentsColumnName"] !== null) {
            $process->commentsColumnName = $params["commentsColumnName"];
        }

        $process->commentsFormat = $params["commentsFormat"];
        $process->commentsAnonymize = $params["commentsAnonymize"];
        $process->commentsExcludeAPI = $params["commentsExcludeAPI"];
        $process->generateSchemeDocs = $params["generateSchemeDocs"];
        $process->note = $params["note"];

        return [
            "id" => $process->run()->id,
        ];
    }
}
