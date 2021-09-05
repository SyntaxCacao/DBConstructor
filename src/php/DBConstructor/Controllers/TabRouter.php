<?php

declare(strict_types=1);

namespace DBConstructor\Controllers;

class TabRouter
{
    /** @var TabController */
    public $current;

    /** @var string */
    public $default;

    /** @var TabController[] */
    public $tabs = [];

    public function register(TabController $tab, bool $default = false)
    {
        $this->tabs[$tab->link] = $tab;

        if ($default) {
            $this->default = $tab->link;
        }
    }

    public function route(array $path, int $tabIndex, &$data): bool
    {
        if (isset($this->default) && ! isset($path[$tabIndex])) {
            $this->current = $this->tabs[$this->default];
            return $this->tabs[$this->default]->request($path, $data);
        } else if (array_key_exists($path[$tabIndex], $this->tabs)) {
            $this->current = $this->tabs[$path[$tabIndex]];
            return $this->tabs[$path[$tabIndex]]->request($path, $data);
        } else {
            (new NotFoundController())->request($path);
            return false;
        }
    }
}
