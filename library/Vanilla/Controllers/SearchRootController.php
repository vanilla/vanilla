<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Controllers;

use Vanilla\Models\ProfileFieldsPreloadProvider;
use Vanilla\Web\PageDispatchController;

/**
 * Page controller for the new search page.
 */
class SearchRootController extends PageDispatchController
{
    const ENABLE_FLAG = "useNewSearchPage";

    private ProfileFieldsPreloadProvider $profileFieldsPreloadProvider;

    /**
     * @param ProfileFieldsPreloadProvider $profileFieldsPreloadProvider
     */
    public function __construct(ProfileFieldsPreloadProvider $profileFieldsPreloadProvider)
    {
        $this->profileFieldsPreloadProvider = $profileFieldsPreloadProvider;
    }

    /**
     * Serve the root search page.
     */
    public function index()
    {
        $page = $this->useSimplePage(t("Search"));
        $page->registerReduxActionProvider($this->profileFieldsPreloadProvider);

        return $page->blockRobots("noindex nofollow")->render();
    }
}
