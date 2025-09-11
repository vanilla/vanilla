<?php
/**
 * @author Isis Graziatto <igraziatto@higherlogic.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Models;

use Garden\Web\Data;
use Gdn_Session;
use Vanilla\APIv2\ProductMessagesApiController;
use Vanilla\Dashboard\Api\DashboardApiController;
use Vanilla\Web\JsInterpop\PreloadedQuery;
use Vanilla\Web\JsInterpop\ReactQueryPreloadProvider;
use Vanilla\Web\JsInterpop\ReduxAction;
use Vanilla\Web\JsInterpop\ReduxActionProviderInterface;

/**
 * Page preloader for dashboard menus data.
 */
class DashboardPreloadProvider implements ReactQueryPreloadProvider
{
    /**
     * DI.
     */
    public function __construct(
        private DashboardApiController $dashboardApi,
        private ProductMessagesApiController $productMessagesApiController,
        private Gdn_Session $session
    ) {
    }

    /**
     * @inheritdoc
     */
    public function createQueries(): array
    {
        $result = [];

        $result[] = new PreloadedQuery(["dashboardMenus"], Data::box($this->dashboardApi->index_menus()));

        if ($this->session->checkPermission("site.manage")) {
            $result[] = new PreloadedQuery(
                ["productMessages"],
                Data::box($this->productMessagesApiController->index())
            );
        }

        return $result;
    }
}
