<?php

declare(strict_types=1);

namespace DBConstructor\Controllers;

abstract class TabController
{
    /** @var string */
    public $icon;

    /** @var string */
    public $label;

    /** @var string */
    public $link;

    public function __construct(string $label, string $link, string $icon)
    {
        $this->label = $label;
        $this->link = $link;
        $this->icon = $icon;
    }

    public abstract function request(array $path, &$data);
}
