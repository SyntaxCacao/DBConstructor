<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\Exports;

use DBConstructor\Application;
use DBConstructor\Controllers\Controller;
use DBConstructor\Controllers\NotFoundController;
use DBConstructor\Models\Export;
use DBConstructor\Models\Participant;
use Exception;

class ExportsController extends Controller
{
    public static function readFile(string $path, string $downloadName)
    {
        header("Content-Description: File Transfer");

        if (preg_match("/\.csv$/", $downloadName) === 1) {
            $contentType = "text/csv; charset=utf-8";
        } else if (preg_match("/\.html$/", $downloadName) === 1) {
            $contentType = "text/html; charset=utf-8";
        } else if (preg_match("/\.zip$/", $downloadName) === 1) {
            $contentType = "application/zip";
        }

        if (isset($contentType)) {
            header("Content-Type: $contentType");
        }

        header("Content-Disposition: attachment; filename=\"$downloadName\"");
        header("Content-Length: ".filesize($path));

        readfile($path);
        exit;
    }

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

            self::readFile($archivePath, $archiveDownloadName);
        } else if (Export::isPossibleFileName($path[2]) && ($fileName = $export->lookUpLocalFile($path[2])) !== null) {
            // Single file download
            self::readFile($export->getLocalDirectoryPath()."/".$fileName, $fileName);
        }

        http_response_code(404);
        $data["page"] = "export_error";
        $data["title"] = "Fehler";
        $data["error"] = "Die angeforderte Datei ist nicht vorhanden oder nicht lesbar.";
        Application::$instance->callTemplate($data);
    }
}
