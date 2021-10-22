<?php

declare(strict_types=1);

namespace DBConstructor\Util;

class TemplateFunctions
{
    /**
     * @param array<array{href: string, icon: string, text: string}> $actions
     */
    static function printMainHeader(string $heading, string $subtitle = null, array $actions = [], bool $showActions = true)
    {
        echo '<header class="main-header">';
        echo '<div class="main-header-header">';
        echo '<h1 class="main-heading">'.$heading.'</h1>';

        if (isset($subtitle)) {
            echo '<p class="main-subtitle">'.$subtitle.'</p>';
        }

        echo '</div>';

        if ($showActions && count($actions) > 0) {
            echo '<div class="main-header-actions main-header-actions-responsive">';

            foreach ($actions as $action) {
                echo '<a class="button button-small" href="';

                if (isset($action["href"])) {
                    echo $action["href"];
                } else {
                    echo '#';
                }

                echo '">';

                if (isset($action["icon"])) {
                    echo '<span class="bi '.$action["icon"].'"></span>';
                }

                echo $action["text"].'</a>';
            }

            echo '<details class="dropdown main-header-actions-more">';
            echo '<summary><span class="button button-small"><span class="bi bi-three-dots" style="margin-right: 0"></span></span></summary>';
            echo '<ul class="dropdown-menu dropdown-menu-down dropdown-menu-left">';

            foreach ($actions as $action) {
                echo '<li class="dropdown-item"><a class="dropdown-link" href="';

                if (isset($action["href"])) {
                    echo $action["href"];
                } else {
                    echo '#';
                }

                echo '">';

                if (isset($action["icon"])) {
                    echo '<span class="bi '.$action["icon"].'"></span>';
                }

                echo $action["text"].'</a></li>';
            }

            echo '</ul></details>';
            echo '</div>';
        }

        echo '</header>';
    }
}
