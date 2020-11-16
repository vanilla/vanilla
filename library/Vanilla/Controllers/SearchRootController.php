<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Controllers;

use Vanilla\Web\PageDispatchController;

/**
 * Page controller for the new search page.
 */
class SearchRootController extends PageDispatchController {

    const ENABLE_FLAG = 'useNewSearchPage';

    /**
     * Serve the root search page.
     */
    public function index() {
        return $this
            ->useSimplePage(t('Search'))
            ->blockRobots("noindex nofollow")
            ->render()
        ;
    }
}
