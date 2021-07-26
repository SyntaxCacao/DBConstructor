<?php

declare(strict_types=1);

namespace DBConstructor\Util;

class MarkdownParser
{
    /** @var int */
    public $headings = 6;

    /** @var bool */
    public $newTab = true;

    public function parse(string $str): string
    {
        // replace any whitespace character except \n with a singl espace
        $str = preg_replace("/(?:\s(?<!\n))+/", " ", $str);

        // remove trailing spaces
        $str = preg_replace("/^ +/m", "", $str);
        $str = preg_replace("/ +$/m", "", $str);

        // remove multiple newlines
        $str = preg_replace("/\n{2,}/", "\n", $str);

        $blocks = explode("\n", $str);
        $html = "";

        foreach ($blocks as $block) {
            $html .= $this->parseBlock($block);
        }

        return $html;
    }

    public function parseBlock(string $str): string
    {
        $matches = [];

        if ($this->headings > 0 && preg_match("/^(#{1,6}) (.*)$/", $str, $matches) && strlen($matches[1]) <= $this->headings) {
            return "<h".strlen($matches[1]).">".$this->parseInline($matches[2])."</h".strlen($matches[1]).">";
        } else {
            return "<p>".$this->parseInline($str)."</p>";
        }
    }

    public function parseInline(string $str): string
    {
        $matches = [];

        if ($str == "") {
            return "";
        } else if (preg_match("/^(.*)\[(.+)]\((.+)\)(.*)$/", $str, $matches)) {
            // link
            if ($this->newTab) {
                return $this->parseInline($matches[1]).'<a href="'.htmlspecialchars($matches[3]).'" target="_blank">'.$this->parseInline($matches[2])."</a>".$this->parseInline($matches[4]);
            } else {
                return $this->parseInline($matches[1]).'<a href="'.htmlspecialchars($matches[3]).'">'.$this->parseInline($matches[2])."</a>".$this->parseInline($matches[4]);
            }
        } else if (preg_match("/^(.*)\*\*(.+)\*\*(.*)$/", $str, $matches) || preg_match("/^(.*)__(.+)__(.*)$/", $str, $matches)) {
            // bold
            return $this->parseInline($matches[1])."<strong>".$this->parseInline($matches[2])."</strong>".$this->parseInline($matches[3]);
        } else if (preg_match("/^(.*)\*(.+)\*(.*)$/", $str, $matches) || preg_match("/^(.*)_(.+)_(.*)$/", $str, $matches)) {
            // italic
            return $this->parseInline($matches[1])."<i>".$this->parseInline($matches[2])."</i>".$this->parseInline($matches[3]);
        } else {
            return htmlspecialchars($str);
        }
    }
}
