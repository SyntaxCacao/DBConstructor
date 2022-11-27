<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\API\Projects\Tables\Records;

use DBConstructor\Controllers\API\LeafNode;
use DBConstructor\Controllers\API\Projects\ProjectsNode;
use DBConstructor\Models\RelationalField;
use DBConstructor\Models\Table;

class ReferencesLeaf extends LeafNode
{
    public function get(array $path): array
    {
        $fields = RelationalField::loadReferencingFields(RecordsNode::$record->id, true);
        $tables = Table::loadList(ProjectsNode::$project->id, ProjectsNode::$project->manualOrder);
        $references = [];

        foreach ($fields as $field) {
            $references[$field->rowTableId][] = [
                "recordId" => intval($field->rowId),
                "recordValid" => $field->rowValid,
                "columnId" => intval($field->columnId),
                "columnName" => $field->columnName,
                "columnLabel" => $field->columnLabel
            ];
        }

        $referencesOrdered = [];

        foreach ($tables as $table) {
            if (isset($references[$table->id])) {
                $referencesOrdered[$table->id] = [
                    "id" => intval($table->id),
                    "name" => $table->name,
                    "label" => $table->label,
                    "references" => $references[$table->id]
                ];
            }
        }

        return [
            "count" => count($fields),
            "tables" => $referencesOrdered
        ];
    }
}
