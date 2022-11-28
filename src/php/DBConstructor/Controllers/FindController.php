<?php

declare(strict_types=1);

namespace DBConstructor\Controllers;

use DBConstructor\Application;
use DBConstructor\Models\Row;

class FindController extends Controller
{
    public function request(array $path)
    {
        if (count($path) !== 1) {
            (new NotFoundController())->request($path);
            return;
        }

        $data["page"] = "find";
        $data["title"] = "Datensatz finden";

        if (isset($_REQUEST["id"])) {
            $data["value"] = $_REQUEST["id"];

            if (! preg_match("/^#? ?0*([1-9]+[0-9]*)$/", $data["value"], $matches)) {
                $data["message"] = "Geben Sie eine gültige ID ein.";
                Application::$instance->callTemplate($data);
                return;
            }

            if (($row = Row::loadWithProjectId(Application::$instance->user->id, $matches[1], $projectId, $isParticipant)) === null || ! $isParticipant) {
                http_response_code(404);
                $data["message"] = "Dieser Datensatz konnte nicht gefunden werden. Möglicherweise wurde er endgültig gelöscht oder gehört zu einem Projekt, an dem Sie nicht beteiligt sind.";
                Application::$instance->callTemplate($data);
                return;
            }

            Application::$instance->redirect("projects/$projectId/tables/$row->tableId/view/$row->id");
        }

        Application::$instance->callTemplate($data);
    }
}
