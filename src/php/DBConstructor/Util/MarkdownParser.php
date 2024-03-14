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

    protected static function init()
    {
        // not using an autoloader
        require_once "../php-vendor/erusev/parsedown/Parsedown.php";

        self::$parsedown = new Parsedown();
        self::$parsedown->setBreaksEnabled(true);
        self::$parsedown->setSafeMode(true);
    }

    public static function parse(string $str): string
    {
        if (self::$parsedown === null) {
            self::init();
        }

        return self::$parsedown->text($str);
    }

    public static function parseLine(string $str): string
    {
        if (self::$parsedown === null) {
            self::init();
        }

        return self::$parsedown->line($str);
    }
}
