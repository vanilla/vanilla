<?php
/**
 * @author Isis Graziatto <igraziatto@higherlogic.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Models;

use Garden\Web\Data;
use Vanilla\Dashboard\Api\DashboardApiController;
use Vanilla\Web\JsInterpop\ReduxAction;
use Vanilla\Web\JsInterpop\ReduxActionProviderInterface;

/**
 * Page preloader for dashboard menus data.
 */
class DashboardPreloadProvider implements ReduxActionProviderInterface
{
    /** @var DashboardApiController */
    private $dashboardApi;

    /**
     * DI.
     *
     * @param DashboardApiController $dashboardApi
     */
    public function __construct(DashboardApiController $dashboardApi)
    {
        $this->dashboardApi = $dashboardApi;
    }

    /**
     * @inheridoc
     */
    public function createActions(): array
    {
        $menus = $this->dashboardApi->index_menus();
        return [new ReduxAction("@@dashboardsections/fetchDashboardSections/fulfilled", Data::box($menus), [])];
    }
}
