<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\API\Projects\Participants;

use DBConstructor\Controllers\API\LeafNode;
use DBConstructor\Controllers\API\Projects\ProjectsNode;
use DBConstructor\Models\Participant;

class ParticipantsListLeaf extends LeafNode
{
    public function get(array $path): array
    {
        $participants = Participant::loadList(ProjectsNode::$project->id);
        $result = [];

        foreach ($participants as $participant) {
            $result[] = [
                "id" => intval($participant->userId),
                "firstName" => $participant->firstName,
                "lastName" => $participant->lastName,
                "isManager" => $participant->isManager,
                "joined" => $participant->created
            ];
        }

        return $result;
    }
}
