<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\API\Projects\Tables\Columns;

use DBConstructor\Controllers\API\InternalNode;
use DBConstructor\Controllers\API\NotFoundException;

class ColumnsNode extends InternalNode
{
    public function process($path): array
    {
        if (count($path) === 6) {
            return (new ColumnsListLeaf)->process($path);
        }

        throw new NotFoundException();
    }
}
