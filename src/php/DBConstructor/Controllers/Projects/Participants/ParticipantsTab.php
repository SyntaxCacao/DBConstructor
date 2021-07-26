<?php

namespace DBConstructor\Controllers\Projects\Participants;

use DBConstructor\Controllers\NotFoundController;
use DBConstructor\Controllers\TabController;
use DBConstructor\Models\Participant;

class ParticipantsTab extends TabController
{
    public function __construct() {
        parent::__construct("Beteiligte", "participants", "people");
    }

    public function request(array $path, &$data): bool
    {
        if (count($path) != 3) {
            (new NotFoundController())->request($path);
            return false;
        }

        $data["participants"] = Participant::loadList($data["project"]->id);

        $data["tabpage"] = "list";

        return true;
    }
}
