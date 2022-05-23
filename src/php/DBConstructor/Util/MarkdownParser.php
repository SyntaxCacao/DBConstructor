<?php

declare(strict_types=1);

namespace DBConstructor\Util;

use Parsedown;

class MarkdownParser
{
    /**
     * @var Parsedown|null
     */
    protected static $parsedown;

    public static function parse(string $str): string
    {
        if (MarkdownParser::$parsedown === null) {
            // not using an autoloader
            require_once "../php-vendor/erusev/parsedown/Parsedown.php";

            MarkdownParser::$parsedown = new Parsedown();
            MarkdownParser::$parsedown->setBreaksEnabled(true);
            MarkdownParser::$parsedown->setSafeMode(true);
        }

        return MarkdownParser::$parsedown->text($str);
    }
}
