<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\Projects\Participants;

use DBConstructor\Application;
use DBConstructor\Controllers\NotFoundController;
use DBConstructor\Controllers\TabController;
use DBConstructor\Models\Participant;
use DBConstructor\Models\User;

class ParticipantsTab extends TabController
{
    public function __construct()
    {
        parent::__construct("Beteiligte", "participants", "people");
    }

    public function request(array $path, &$data): bool
    {
        if (count($path) == 4 && $path[3] == "add") {
            $form = new ParticipantAddForm();
            $form->init($data["project"]->id);
            $form->process();
            $data["form"] = $form;

            $data["tabpage"] = "add";
            $data["title"] = "Benutzer hinzufügen";
            return true;
        }

        if (count($path) != 3) {
            (new NotFoundController())->request($path);
            return false;
        }

        // Participants list
        $data["managerCount"] = Participant::countManagers($data["project"]->id);

        // Perform updates
        // TODO: Permission check
        if (isset($_REQUEST["demote"]) && intval($_REQUEST["demote"]) != 0) {
            $participant = Participant::load($data["project"]->id, $_REQUEST["demote"]);

            if (! is_null($participant) && $data["managerCount"] > 1) {
                $participant->demote();
                $data["managerCount"] -= 1;
            }
        }

        if (isset($_REQUEST["promote"]) && intval($_REQUEST["promote"]) != 0) {
            $participant = Participant::load($data["project"]->id, $_REQUEST["promote"]);

            if (! is_null($participant)) {
                $participant->promote();
                $data["managerCount"] += 1;
            }
        }

        if (isset($_REQUEST["remove"]) && intval($_REQUEST["remove"]) != 0) {
            $participant = Participant::load($data["project"]->id, $_REQUEST["remove"]);

            if (! is_null($participant) && (! $participant->isManager || $data["managerCount"] > 1)) {
                $participant->delete();
                $data["managerCount"] -= 1;
            }
        }

        // Show list
        /* TODO: Permission check
        if ($data["isManager"]) {*/
            $data["notParticipatingCount"] = User::countNotParticipating($data["project"]->id);
        //}

        $data["participants"] = Participant::loadList($data["project"]->id);

        $data["tabpage"] = "list";
        return true;
    }
}
