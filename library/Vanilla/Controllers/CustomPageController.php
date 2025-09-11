<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Controllers;

use Garden\Web\Data;
use Vanilla\Dashboard\Controllers\API\CustomPagesApiController;
use Vanilla\Layout\Asset\LayoutQuery;
use Vanilla\Layout\CustomPageLayoutRecordProvider;
use Vanilla\Layout\LayoutPage;
use Vanilla\Layout\View\CustomPageLayoutView;
use Vanilla\Web\PageDispatchController;

class CustomPageController extends PageDispatchController
{
    public function __construct(private CustomPagesApiController $customPagesApi)
    {
    }

    /**
     * Handle the custom page request by preloading the layout and rendering the page.
     *
     * @param int $customPageID
     * @return Data
     */
    public function __invoke(int $customPageID): \Garden\Web\Data
    {
        // Use api to fetch custom page for permission checks.
        $this->customPagesApi->get($customPageID);

        $query = new LayoutQuery(
            CustomPageLayoutView::VIEW_TYPE,
            CustomPageLayoutRecordProvider::RECORD_TYPE,
            $customPageID,
            ["customPageID" => $customPageID]
        );

        $page = $this->usePage(LayoutPage::class)
            ->permission()
            ->setSeoRequired(false)
            ->preloadLayout($query);

        return $page->render();
    }
}
