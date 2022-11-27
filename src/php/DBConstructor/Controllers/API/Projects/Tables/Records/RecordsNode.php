<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\API\Projects\Tables\Records;

use DBConstructor\Controllers\API\InternalNode;
use DBConstructor\Controllers\API\NotFoundException;
use DBConstructor\Controllers\API\Projects\Tables\TablesNode;
use DBConstructor\Models\Row;

class RecordsNode extends InternalNode
{
    /** @var Row */
    public static $record;

    public function process($path): array
    {
        if (count($path) === 6) {
            return (new RecordsListLeaf())->process($path);
        }

        if (intval($path[6]) === 0 || (self::$record = Row::load($path[6])) === null || self::$record->tableId !== TablesNode::$table->id) {
            throw new NotFoundException();
        }

        if (count($path) === 7) {
            return (new RecordsElementLeaf())->process($path);
        }

        if (count($path) >= 8 && $path[7] === "attachments") {
            if (count($path) === 8) {
                return (new AttachmentsListLeaf())->process($path);
            }

            if (count($path) === 9) {
                return (new AttachmentsElementLeaf())->process($path);
            }
        }

        if (count($path) === 8 && $path[7] === "references") {
            return (new ReferencesLeaf())->process($path);
        }

        throw new NotFoundException();
    }
}
