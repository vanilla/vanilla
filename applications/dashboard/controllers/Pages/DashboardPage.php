<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

use Vanilla\Models\DashboardPreloadProvider;
use Vanilla\Web\ThemedPage;

/**
 * Base page for rendering new admin layouts built in react.
 */
class DashboardPage extends ThemedPage
{
    protected $forcedThemeKey = "theme-dashboard";

    /**
     * @inheritdoc
     */
    public function initialize(): self
    {
        // Nothing for now.
    }

    /**
     * @inheritdoc
     */
    public function getAssetSection(): string
    {
        return "admin-new";
    }

    /**
     * Initialize data that is shared among the controllers.
     * Overwriting the base method to add actions from DashboardPreloadProvider.
     */
    protected function initAssets()
    {
        // Preload for frontend
        /** @var DashboardPreloadProvider $dashboardProvider */
        $dashboardProvider = \Gdn::getContainer()->get(DashboardPreloadProvider::class);
        $this->registerReduxActionProvider($dashboardProvider);
        parent::initAssets();
    }
}
