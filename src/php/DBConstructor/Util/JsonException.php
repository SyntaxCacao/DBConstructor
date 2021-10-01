<?php

declare(strict_types=1);

namespace DBConstructor\Util;

use Exception;

/**
 * The flag JSON_THROW_ON_ERROR, which makes json_encode and json_decode
 * throw a \JsonException instead of returning false, is available as of
 * PHP 7.3.0. When upgrading to that version, this class should be removed
 * and said flag should be used instead.
 */
class JsonException extends Exception
{
    public function __construct()
    {
        parent::__construct("Error while executing json_encode or json_decode: ".json_last_error_msg());
    }
}
