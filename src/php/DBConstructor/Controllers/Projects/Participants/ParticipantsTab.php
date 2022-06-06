<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\Projects\Participants;

use DBConstructor\Application;
use DBConstructor\Controllers\ForbiddenController;
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

    public function request(array $path, array &$data): bool
    {
        if (count($path) == 4 && $path[3] == "add") {
            if (! $data["isManager"]) {
                (new ForbiddenController())->request($path);
                return false;
            }

            $form = new ParticipantAddForm();
            $form->init();
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
        if ($data["isManager"]) {
            // Demotion
            if (isset($_REQUEST["demote"]) && intval($_REQUEST["demote"]) != 0) {
                $participant = Participant::loadFromId($data["project"]->id, $_REQUEST["demote"]);

                if (! is_null($participant) && $data["managerCount"] > 1) {
                    $participant->demote();
                    $data["managerCount"] -= 1;
                }
            }

            // Promotion
            if (isset($_REQUEST["promote"]) && intval($_REQUEST["promote"]) != 0) {
                $participant = Participant::loadFromId($data["project"]->id, $_REQUEST["promote"]);

                if (! is_null($participant)) {
                    $participant->promote();
                    $data["managerCount"] += 1;
                }
            }

            // Removal
            if (isset($_REQUEST["remove"]) && intval($_REQUEST["remove"]) != 0) {
                $participant = Participant::loadFromId($data["project"]->id, $_REQUEST["remove"]);

                if (! is_null($participant) && (! $participant->isManager || $data["managerCount"] > 1)) {
                    $participant->remove();
                    $data["managerCount"] -= 1;

                    if ($participant->userId == Application::$instance->user->id) {
                        Application::$instance->redirect(null, "left");
                    }
                }
            }
        }

        // Show list
        $data["participants"] = Participant::loadList($data["project"]->id);

        if ($data["isManager"]) {
            $data["notParticipatingCount"] = User::countNotParticipating($data["project"]->id);
        }

        $data["tabpage"] = "list";
        return true;
    }
}
