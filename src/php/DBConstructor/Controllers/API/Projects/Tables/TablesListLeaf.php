<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\API\Projects\Tables;

use DBConstructor\Application;
use DBConstructor\Controllers\API\LeafNode;
use DBConstructor\Controllers\API\Projects\ProjectsNode;
use DBConstructor\Models\Table;

class TablesListLeaf extends LeafNode
{
    public function get(array $path): array
    {
        $tables = Table::loadList(ProjectsNode::$project->id, ProjectsNode::$project->manualOrder, true, true, Application::$instance->user->id);
        $result = [];

        foreach ($tables as $table) {
            $result[] = [
                "id" => intval($table->id),
                "name" => $table->name,
                "label" => $table->label,
                "records" => intval($table->rowCount),
                "assignedRecords" => intval($table->assignedCount),
                "created" => $table->created
            ];
        }

        return $result;
    }
}
