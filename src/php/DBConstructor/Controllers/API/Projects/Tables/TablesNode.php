<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\API\Projects\Tables;

use DBConstructor\Controllers\API\InternalNode;
use DBConstructor\Controllers\API\NotFoundException;
use DBConstructor\Controllers\API\Projects\ProjectsNode;
use DBConstructor\Controllers\API\Projects\Tables\Columns\ColumnsNode;
use DBConstructor\Controllers\API\Projects\Tables\Records\RecordsNode;
use DBConstructor\Models\Table;

class TablesNode extends InternalNode
{
    /** @var Table */
    public static $table;

    public function process($path): array
    {
        if (count($path) === 4) {
            return (new TablesListLeaf())->process($path);
        }

        if (intval($path[4]) === 0 || (self::$table = Table::load($path[4])) === null || self::$table->projectId !== ProjectsNode::$project->id) {
            throw new NotFoundException();
        }

        if (count($path) === 5) {
            return (new TablesElementLeaf())->process($path);
        }

        if ($path[5] === "columns") {
            return (new ColumnsNode())->process($path);
        }

        if ($path[5] === "records") {
            return (new RecordsNode())->process($path);
        }

        throw new NotFoundException();
    }
}
