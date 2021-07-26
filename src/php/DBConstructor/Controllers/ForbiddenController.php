<?php

declare(strict_types=1);

namespace DBConstructor\Controllers;

use DBConstructor\Application;

class ForbiddenController extends Controller
{
    public function isPublic(): bool
    {
        return true;
    }

    public function request(array $path)
    {
        http_response_code(403);

        $data["page"] = "forbidden";
        $data["title"] = "Zugriff nicht gestattet";

        Application::$instance->callTemplate($data);
    }
}
