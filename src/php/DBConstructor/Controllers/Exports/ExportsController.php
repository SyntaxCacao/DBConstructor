<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\Exports;

use DBConstructor\Application;
use DBConstructor\Controllers\Controller;
use DBConstructor\Controllers\NotFoundController;
use DBConstructor\Models\Export;

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

        $fileName = $export->getFileName().".zip";

        if (! $path[2] == $fileName) {
            (new NotFoundController())->request($path);
            return;
        }

        if ($export->deleted) {
            http_response_code(404);
            $data["page"] = "export_error";
            $data["title"] = "Exports gelöscht";
            $data["error"] = "Dieser Exports wurde gelöscht.";
            Application::$instance->callTemplate($data);
            return;
        }

        // TODO: Use const for directory name
        $filePath = "../tmp/exports/export-$export->id.zip";

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
            $data["error"] = "Diese Exportdatei konnte nicht gelesen werden.";
            Application::$instance->callTemplate($data);
            return;
        }

        header('Content-Description: File Transfer');
        header("Content-Type: application/zip");
        header("Content-Disposition: attachment; filename=$fileName");
        header("Content-Length: ".filesize($filePath));

        readfile($filePath);
        exit;
    }
}
