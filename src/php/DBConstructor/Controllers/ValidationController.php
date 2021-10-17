<?php

declare(strict_types=1);

namespace DBConstructor\Controllers;

use DBConstructor\Models\TextualColumn;
use DBConstructor\Util\JsonException;

class ValidationController extends Controller
{
    /**
     * @throws JsonException
     */
    public function request(array $path)
    {
        if (count($path) !== 1 || ! isset($_REQUEST["id"]) || ! isset($_REQUEST["value"])) {
            (new NotFoundController())->request($path);
            return;
        }

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
    }
}
