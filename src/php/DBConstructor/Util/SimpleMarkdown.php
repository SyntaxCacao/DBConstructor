<?php

declare(strict_types=1);

namespace DBConstructor\Util;

class SimpleMarkdown
{
    public static function format(string $src): string
    {
        $src = htmlentities($src);

        // Remove multiple newlines
        $src = preg_replace("/\s*\n+\s*/", "\n", $src);

        $lines = explode("\n", $src);
        $html = "";

        foreach ($lines as $line) {
            // bold
            $line = preg_replace("/\*(.+)\*/", '<strong>$1</strong>', $line);

            // italic
            $line = preg_replace("/_(.+)_/", '<i>$1</i>', $line);

            // link
            $line = preg_replace("/\[(.+)]\((.+)\)/", '<a href="$2" target="_blank">$1</a>', $line);

            if (preg_match("/^# .+/", $line)) {
                $html .= preg_replace("/^# (.+)/", '<h2>$1</h2>', $line);
            } else {
                $html .= "<p>$line</p>";
            }
        }

        return $html;
    }

    public static function removeMarkup(string $markup): string
    {
        $markup = htmlentities($markup);

        // bold
        $markup = preg_replace("/^(.*)\*(.+)\*(.*)$/m", "$1$2$3", $markup);

        // italic
        $markup = preg_replace("/^(.*)_(.+)_(.*)$/m", "$1$2$3", $markup);

        // link
        $markup = preg_replace("/^(.*)\[(.+)]\(.+\)(.*)$/m", "$1$2$3", $markup);

        // heading
        $markup = preg_replace("/^# (.*)/m", "$1", $markup);

        // whitespace
        $markup = preg_replace("/\s+/", " ", $markup);

        return $markup;
    }
}
