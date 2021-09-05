<?php

declare(strict_types=1);

namespace DBConstructor\Util;

class MarkdownParser
{
    /** @var int */
    public $headings = 3;

    /** @var bool */
    public $newTab = true;

    public function parse(string $str): string
    {
        // remove \r
        $str = preg_replace("/\r/", "", $str);

        $matches = [];

        if ($str == "") {
            return "";
        } else if (preg_match("/^(\X*?)(?:\A|\n)`{3,}\n(\X*?)\n`{3,}(?:\n|\z)(\X*)$/", $str, $matches)) {
            // code block
            return $this->parse($matches[1])."<pre>".htmlspecialchars($matches[2])."</pre>".$this->parse($matches[3]);
        } else {
            // replace any whitespace character except \n with a single space
            $str = preg_replace("/(?:\s(?<!\n))+/", " ", $str);

            // remove trailing spaces
            $str = preg_replace("/^ +/m", "", $str);
            $str = preg_replace("/ +$/m", "", $str);

            // remove multiple newlines
            $str = preg_replace("/\n{3,}/", "\n\n", $str);

            return $this->parseLines($str);
        }
    }

    public function parseLines(string $str): string
    {
        $matches = [];

        if ($str == "") {
            return "";
        } else if ($this->headings > 0 && preg_match("/^(\X*?)(?:\A|\n)(#{1,".$this->headings."}) (.*)(?:\n|\z)(\X*)$/", $str, $matches)) {
            // heading
            return $this->parseLines($matches[1])."<h".strlen($matches[2]).">".$this->parseInline($matches[3])."</h".strlen($matches[2]).">".$this->parseLines($matches[4]);
        } else if (preg_match("/^(\X*?)(?:\A|\n)((?:>.*(?:\n|\z))+)(\X*)$/", $str, $matches)) { // prev /^((?:(?:[^>\n].*)?\n)*)((?:>.*(?:\n|\z))+)((?:[^>](?:.|\n)*)?)$/
            // blockquote
            return $this->parseLines($matches[1])."<blockquote>".$this->parseLines(preg_replace("/^> ?/m", "", $matches[2]))."</blockquote>".$this->parseLines($matches[3]);
        } else if (preg_match("/^(\X*?)(?:\A|\n)((?:[*\-+] .*(?:\n|\z))+)(\X*)$/", $str, $matches)) {
            // unordered list
            $lines = explode("\n", preg_replace("/^[*\-+] /m", "", trim($matches[2], "\n")));
            $html = "";

            foreach ($lines as $line) {
                $html .= "<li>".$this->parseInline($line)."</li>";
            }

            return $this->parseLines($matches[1])."<ul>".$html."</ul>".$this->parseLines($matches[3]);
        } else if (preg_match("/^(\X*?)(?:\A|\n)((?:[0-9]+\. .*(?:\n|\z))+)(\X*)$/", $str, $matches)) {
            // ordered list
            $lines = explode("\n", preg_replace("/^[0-9]+\. /m", "", trim($matches[2], "\n")));
            $html = "";

            foreach ($lines as $line) {
                $html .= "<li>".$this->parseInline($line)."</li>";
            }

            return $this->parseLines($matches[1])."<ol>".$html."</ol>".$this->parseLines($matches[3]);
        } else if (preg_match("/^(\X*?)(?:\A|\n)(?:_{3,}|-{3,})(?:\n|\z)(\X*)$/", $str, $matches)) {
            // horizontal rule
            return $this->parseLines($matches[1])."<hr>".$this->parseLines($matches[2]);
        } else {
            // paragraph
            $paragraphs = explode("\n\n", $str);
            $html = "";

            foreach ($paragraphs as $paragraph) {
                $lines = explode("\n", trim($paragraph, "\n"));
                $html .= "<p>";

                for ($i = 0; $i < count($lines); $i++) {
                    $html .= $this->parseInline($lines[$i]);

                    if ($i < count($lines) - 1) {
                        $html .= "<br>";
                    }
                }

                $html .= "</p>";
            }

            return $html;
        }
    }

    public function parseInline(string $str): string
    {
        $matches = [];

        if ($str == "") {
            return "";
        } else if (preg_match("/^(.*)\[(.+)]\((https?:\/\/[a-zA-ZÄÖÜäöü0-9:\-.]+(?:\/[a-zA-ZÄÖÜäöü0-9.\/=\-_~?&#:+]*)?)\)(.*)$/", $str, $matches)) {
            // link - previously /^(.*)\[(.+)]\((.+)\)(.*)$/
            if ($this->newTab) {
                return $this->parseInline($matches[1]).'<a href="'.htmlspecialchars($matches[3]).'" target="_blank">'.$this->parseInline($matches[2])."</a>".$this->parseInline($matches[4]);
            } else {
                return $this->parseInline($matches[1]).'<a href="'.htmlspecialchars($matches[3]).'">'.$this->parseInline($matches[2])."</a>".$this->parseInline($matches[4]);
            }
        } else if (preg_match("/^(.*)\*\*(.+)\*\*(.*)$/U", $str, $matches) || preg_match("/^(.*)__(.+)__(.*)$/U", $str, $matches)) {
            // bold
            return $this->parseInline($matches[1])."<strong>".$this->parseInline($matches[2])."</strong>".$this->parseInline($matches[3]);
        } else if (preg_match("/^(.*)\*(.+)\*(.*)$/U", $str, $matches) || preg_match("/^(.*)_(.+)_(.*)$/U", $str, $matches)) {
            // italic
            return $this->parseInline($matches[1])."<em>".$this->parseInline($matches[2])."</em>".$this->parseInline($matches[3]);
        } else if (preg_match("/^(.*)~~(.+)~~(.*)$/U", $str, $matches)) {
            // strikethrough
            return $this->parseInline($matches[1])."<s>".$this->parseInline($matches[2])."</s>".$this->parseInline($matches[3]);
        } else if (preg_match("/^(.*)`(.+)`(.*)$/U", $str, $matches)) {
            // code
            return $this->parseInline($matches[1])."<code>".htmlspecialchars($matches[2])."</code>".$this->parseInline($matches[3]);
        } else if (preg_match("/^(.*)(https?:\/\/[a-zA-ZÄÖÜäöü0-9:\-.]+(?:\/[a-zA-ZÄÖÜäöü0-9.\/=\-_~?&#:+]*)?)(.*)$/", $str, $matches)) {
            // automatic linking
            // last check, not necessary to do recursion again for surrounding parts, calling htmlspecialchars directly instead
            return htmlspecialchars($matches[1]).'<a href="'.htmlspecialchars($matches[2]).'" target="blank">'.htmlspecialchars($matches[2])."</a>".htmlspecialchars($matches[3]);
        } else {
            return htmlspecialchars($str);
        }
    }
}
