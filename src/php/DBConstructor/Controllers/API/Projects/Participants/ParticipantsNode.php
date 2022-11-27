<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\API\Projects\Participants;

use DBConstructor\Controllers\API\InternalNode;
use DBConstructor\Controllers\API\NotFoundException;

class ParticipantsNode extends InternalNode
{
    public function process($path): array
    {
        if (count($path) === 4) {
            return (new ParticipantsListLeaf())->process($path);
        }

        throw new NotFoundException();
    }
}
