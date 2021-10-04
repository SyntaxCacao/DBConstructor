<?php

declare(strict_types=1);

namespace DBConstructor\Controllers;

use DBConstructor\Util\MarkdownParser;

class MarkdownController extends Controller
{
    public function request(array $path)
    {
        if (count($path) !== 1 || ! isset($_REQUEST["src"])) {
            (new NotFoundController())->request($path);
            return;
        }

        echo (new MarkdownParser())->parse($_REQUEST["src"]);
        exit;
    }
}
