<?php

declare(strict_types=1);

namespace DBConstructor\Controllers;

use DBConstructor\Models\TextualColumn;
use DBConstructor\Util\JsonException;
use DBConstructor\Util\MarkdownParser;

class APIController extends Controller
{
    /**
     * @throws JsonException
     */
    public function request(array $path)
    {
        // validation
        if (count($path) === 2 && $path[1] === "validation") {
            $column = TextualColumn::load($_REQUEST["id"]);

            if ($column === null) {
                (new NotFoundController())->request($path);
                return;
            }

            $value = $_REQUEST["value"];

            if ($value === "") {
                $value = null;
            }

            $validator = $column->getValidationType()->buildValidator();
            $success = $validator->validate($value);
            echo $column->generateIndicator($validator, $success);
            return;
        }

        // markdown
        if (count($path) === 2 && $path[1] === "markdown") {
            echo MarkdownParser::parse($_REQUEST["src"]);
            return;
        }

        (new NotFoundController())->request($path);
    }
}
