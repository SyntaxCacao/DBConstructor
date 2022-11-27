<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\API\Projects\Tables;

use DBConstructor\Controllers\API\LeafNode;
use DBConstructor\Util\MarkdownParser;

class TablesElementLeaf extends LeafNode
{
    public function get(array $path): array
    {
        return [
            "id" => intval(TablesNode::$table->id),
            "name" => TablesNode::$table->name,
            "label" => TablesNode::$table->label,
            "instructionsMarkdown" => TablesNode::$table->instructions,
            "instructionsHTML" => TablesNode::$table->instructions === null ? null : MarkdownParser::parse(TablesNode::$table->instructions)
        ];
    }
}
