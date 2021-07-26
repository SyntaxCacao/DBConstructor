<?php

declare(strict_types=1);

namespace DBConstructor\Controllers;

use DBConstructor\Application;

class NotFoundController extends Controller
{
    public function isPublic(): bool
    {
        return true;
    }

    public function request(array $path)
    {
        http_response_code(404);

        $data["page"] = "notfound";
        $data["title"] = "Seite nicht gefunden";

        Application::$instance->callTemplate($data);
    }
}
