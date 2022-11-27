<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\API\Projects\Tables\Columns;

use DBConstructor\Controllers\API\LeafNode;
use DBConstructor\Controllers\API\Projects\Tables\TablesNode;
use DBConstructor\Models\RelationalColumn;
use DBConstructor\Models\TextualColumn;
use DBConstructor\Util\MarkdownParser;

class ColumnsListLeaf extends LeafNode
{
    public function get(array $path): array
    {
        $params = $this->processQueryParameters([
            "instructions" => [
                "type" => "boolean",
                "default" => false
            ]
        ]);

        $relationalColumns = RelationalColumn::loadList(TablesNode::$table->id);
        $textualColumns = TextualColumn::loadList(TablesNode::$table->id);

        $result = [];
        $result["relational"] = [];

        foreach ($relationalColumns as $column) {
            $columnResult = [
                "id" => intval($column->id),
                "name" => $column->name,
                "label" => $column->label,
                "targetTable" => [
                    "id" => intval($column->targetTableId),
                    "name" => $column->targetTableName,
                    "label" => $column->targetTableLabel
                ]
            ];

            if ($params["instructions"]) {
                $columnResult["instructions"]["markdown"] = $column->instructions;
                $columnResult["instructions"]["html"] = $column->instructions === null ? null : MarkdownParser::parse($column->instructions);
            }

            $columnResult["hide"] = $column->hide;
            $columnResult["created"] = $column->created;

            $result["relational"][] = $columnResult;
        }

        $result["textual"] = [];

        foreach ($textualColumns as $column) {
            $columnResult = [
                "id" => intval($column->id),
                "name" => $column->name,
                "label" => $column->label
            ];

            if ($params["instructions"]) {
                $columnResult["instructions"]["markdown"] = $column->instructions;
                $columnResult["instructions"]["html"] = $column->instructions === null ? null : MarkdownParser::parse($column->instructions);
            }

            $columnResult["type"] = $column->type;
            $columnResult["typeLabel"] = $column->getTypeLabel();
            $columnResult["rules"] = $column->getValidationType()->toArray();

            $columnResult["hide"] = $column->hide;
            $columnResult["created"] = $column->created;

            $result["textual"][] = $columnResult;
        }

        return $result;
    }
}
