<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\Exports;

use DBConstructor\Application;
use DBConstructor\Controllers\Controller;
use DBConstructor\Controllers\NotFoundController;
use DBConstructor\Models\Export;
use DBConstructor\Models\Participant;

class ExportsController extends Controller
{
    public function request(array $path)
    {
        if (count($path) != 3) {
            (new NotFoundController())->request($path);
            return;
        }

        $exportId = intval($path[1]);

        if ($exportId == 0) {
            // => not int
            (new NotFoundController())->request($path);
            return;
        }

        $export = Export::load($path[1]);

        if ($export == null) {
            (new NotFoundController())->request($path);
            return;
        }

        if (($participant = Participant::loadFromUser($export->projectId, Application::$instance->user->id)) === null ||
            ! $participant->isManager) {
            http_response_code(403);
            $data["page"] = "export_error";
            $data["title"] = "Zugriff verwehrt";
            $data["error"] = "Der Zugriff auf diese Exportdatei ist Ihnen nicht gestattet.";
            Application::$instance->callTemplate($data);
            return;
        }

        $fileName = $export->getFileName().".zip";

        if (! $path[2] == $fileName) {
            (new NotFoundController())->request($path);
            return;
        }

        if ($export->deleted) {
            http_response_code(404);
            $data["page"] = "export_error";
            $data["title"] = "Exportdatei gelöscht";
            $data["error"] = "Diese Exportdatei wurde gelöscht.";
            Application::$instance->callTemplate($data);
            return;
        }

        $filePath = Export::getLocalArchiveName($export->id);

        if (! file_exists($filePath)) {
            http_response_code(404);
            $data["page"] = "export_error";
            $data["title"] = "Fehler";
            $data["error"] = "Diese Exportdatei existiert auf dem Server nicht mehr.";
            Application::$instance->callTemplate($data);
            return;
        }

        if (! is_readable($filePath)) {
            http_response_code(404);
            $data["page"] = "export_error";
            $data["title"] = "Fehler";
            $data["error"] = "Die Exportdatei kann nicht gelesen werden.";
            Application::$instance->callTemplate($data);
            return;
        }

        header("Content-Description: File Transfer");
        header("Content-Type: application/zip");
        header("Content-Disposition: attachment; filename=\"$fileName\"");
        header("Content-Length: ".filesize($filePath));

        readfile($filePath);
        exit;
    }
}
