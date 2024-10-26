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
        if (count($path) !== 3 || ! ctype_digit($path[1]) ||
            ($export = Export::load($path[1])) === null) {
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

        if ($path[2] === ($archiveDownloadName = $export->getArchiveDownloadName())) {
            // Archive download

            $archivePath = $export->getLocalArchivePath();

            if (! file_exists($archivePath)) {
                http_response_code(404);
                $data["page"] = "export_error";
                $data["title"] = "Fehler";
                $data["error"] = "Diese Exportdatei existiert auf dem Server nicht mehr.";
                Application::$instance->callTemplate($data);
                return;
            }

            if (! is_readable($archivePath)) {
                http_response_code(404);
                $data["page"] = "export_error";
                $data["title"] = "Fehler";
                $data["error"] = "Die Exportdatei kann nicht gelesen werden.";
                Application::$instance->callTemplate($data);
                return;
            }

            $this->readFile($archivePath, $archiveDownloadName, "application/zip");
        } else if (Export::isPossibleFileName($path[2]) && ($fileName = $export->lookUpLocalFile($path[2])) !== null) {
            // Single file download

            $filePath = $export->getLocalDirectoryPath()."/".$fileName;

            if (preg_match("/\.csv$/", $fileName) === 1) {
                // CSV
                $this->readFile($filePath, $fileName, "text/csv");
            } else if (preg_match("/\.html$/", $fileName) === 1) {
                // HTML
                $this->readFile($filePath, $fileName, "text/html");
            }
        }

        http_response_code(404);
        $data["page"] = "export_error";
        $data["title"] = "Fehler";
        $data["error"] = "Die angeforderte Datei ist nicht vorhanden oder nicht lesbar.";
        Application::$instance->callTemplate($data);
    }

    public function readFile(string $path, string $downloadName, string $contentType)
    {
        header("Content-Description: File Transfer");
        header("Content-Type: $contentType");
        header("Content-Disposition: attachment; filename=\"$downloadName\"");
        header("Content-Length: ".filesize($path));

        readfile($path);
        exit;
    }
}
