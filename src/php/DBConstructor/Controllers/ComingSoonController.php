<?php

declare(strict_types=1);

namespace DBConstructor\Controllers;

use DBConstructor\Application;

class ComingSoonController extends Controller
{
    public function request(array $path)
    {
        http_response_code(404);

        $data["page"] = "comingsoon";
        $data["title"] = "Noch nicht verfügbar";

        Application::$instance->callTemplate($data);
    }
}
