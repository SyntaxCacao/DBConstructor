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

    /** @var bool */
    public $requireManager;

    public function __construct(string $label, string $link, string $icon, bool $requireManager = false)
    {
        $this->label = $label;
        $this->link = $link;
        $this->icon = $icon;
        $this->requireManager = $requireManager;
    }

    /**
     * @param array<string> $path
     * @param array<string, mixed> $data
     */
    public abstract function request(array $path, array &$data);
}
