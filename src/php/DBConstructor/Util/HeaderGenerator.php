<?php

declare(strict_types=1);

namespace DBConstructor\Util;

/**
 * Generator for .main-header element with responsive actions.
 */
class HeaderGenerator
{
    /**
     * HTML that will be inserted between the buttons and the dropdown menu
     *
     * @var string|null
     */
    public $additionalHTML;

    /**
     * Actions that will be shown as buttons on wide screens and inside a dropdown menu on smaller screens
     *
     * @var array<array{confirm: string|null, danger: bool|null, divider: bool|null, href: string, icon: string|null, text: string}>
     */
    public $autoActions = [];

    /**
     * Action that will always be shown as button
     *
     * @var array<array{confirm: string|null, danger: bool|null, href: string, icon: string|null, text: string}>
     */
    public $buttonActions = [];

    /**
     * Action that will always be shown inside a dropdown menu
     *
     * @var array<array{confirm: string|null, danger: bool|null, divider: bool|null, href: string, icon: string|null, text: string}>
     */
    public $dropdownActions = [];

    /** @var bool */
    public $escapeTitle = true;

    /** @var string|null */
    public $subtitle;

    /** @var string */
    public $title;

    public function __construct(string $title)
    {
        $this->title = $title;
    }

    public function generate()
    {
        echo '<div class="main-header">';

        // header
        echo '<header class="main-header-header">';
        echo '<h1 class="main-heading">'.($this->escapeTitle ? htmlentities($this->title) : $this->title).'</h1>';

        if ($this->subtitle !== null) {
            echo '<p class="main-subtitle">'.htmlentities($this->subtitle).'</p>';
        }

        echo '</header>';

        // actions
        if (count($this->autoActions) > 0 || count($this->buttonActions) > 0 || count($this->dropdownActions) > 0 || isset($this->additionalHTML)) {
            echo '<nav class="main-header-actions">';

            // buttons
            if (count($this->buttonActions) > 0) {
                // buttonActions
                foreach ($this->buttonActions as $action) {
                    $this->generateAction($action, true, true);
                }
            }

            if (count($this->autoActions) > 0) {
                // autoActions
                foreach ($this->autoActions as $action) {
                    $this->generateAction($action, true, false);
                }
            }

            if ($this->additionalHTML !== null) {
                echo $this->additionalHTML;
            }

            // dropdown
            if (count($this->autoActions) > 0 || count($this->dropdownActions) > 0) {
                echo '<details class="dropdown';

                if (count($this->dropdownActions) < 1) {
                    echo ' hide-up-sm';
                }

                echo '">';

                echo '<summary>'; // TODO Remove style attribute
                echo '<span class="button button-small"><span class="bi bi-three-dots" style="margin-right: 0"></span></span>';
                echo '</summary>';
                echo '<ul class="dropdown-menu dropdown-menu-down dropdown-menu-left">';

                // autoActions
                foreach ($this->autoActions as $action) {
                    $this->generateAction($action, false, false);
                }

                // dropdownActions
                foreach ($this->dropdownActions as $action) {
                    $this->generateAction($action, false, true);
                }

                echo '</ul></details>';
            }

            echo '</nav>';
        }

        echo '</div>';
    }

    /**
     * @param bool $isButton true if button, false if dropdown item
     */
    protected function generateAction(array $action, bool $isButton, bool $always)
    {
        if ($isButton) {
            echo '<a class="button button-small';

            if (isset($action["danger"]) && $action["danger"] === true) {
                echo ' button-danger';
            }

            if (isset($action["selected"]) && $action["selected"] === true) {
                echo ' button-selected';
            }

            echo ' main-header-action';

            if (! $always) {
                echo ' hide-down-sm';
            }
        } else {
            echo '<li class="dropdown-item">';
            echo '<a class="dropdown-link';

            if (! $always) {
                echo ' hide-up-sm';
            }
        }

        if (isset($action["confirm"])) {
            echo ' js-confirm';
        }

        echo '"';

        if (isset($action["href"])) {
            echo ' href="'.htmlentities($action["href"]).'"';
        } else {
            echo ' href="?"';
        }

        if (isset($action["confirm"])) {
            echo ' data-confirm-message="'.htmlentities($action["confirm"]).'"';
        }

        echo '>';

        if (isset($action["icon"])) {
            echo '<span class="bi bi-'.$action["icon"].'"></span>';
        }

        echo htmlentities($action["text"]);

        if ($isButton) {
            echo '</a>';
        } else {
            echo '</a></li>';

            if (isset($action["divider"]) && $action["divider"] === true) {
                echo '<li><hr class="dropdown-divider"></li>';
            }
        }
    }
}
