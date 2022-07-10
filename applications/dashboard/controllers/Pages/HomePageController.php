<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Controllers\Pages;

use Vanilla\Layout\LayoutPage;
use Vanilla\Web\PageDispatchController;

/**
 * Controller for the custom layout homepage.
 */
class HomePageController extends PageDispatchController {

    /**
     * Homepage index.
     */
    public function index() {
        return $this->usePage(LayoutPage::class)
            ->permission()
            ->setSeoRequired(false)
            ->setSeoTitle("Home")
            ->render();
    }
}
