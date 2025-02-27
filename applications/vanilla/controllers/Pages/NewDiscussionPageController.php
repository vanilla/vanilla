<?php
/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Controllers\Pages;

use Garden\Web\Data;
use Vanilla\Layout\Asset\LayoutQuery;
use Vanilla\Layout\LayoutPage;
use Vanilla\Web\PageDispatchController;

class NewDiscussionPageController extends PageDispatchController
{
    public function index(string $path): Data
    {
        $page = $this->usePage(LayoutPage::class);
        $page->preloadLayout(new LayoutQuery("newDiscussion", "newDiscussion"));
        $data = $page->setSeoTitle(t("New Discussion"))->render();

        return $data;
    }
}
