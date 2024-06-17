<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

use Garden\Web\Data;
use Vanilla\Dashboard\Events\DashboardAccessEvent;
use Vanilla\Logging\AuditLogger;
use Vanilla\Models\DashboardPreloadProvider;
use Vanilla\Utility\Timers;
use Vanilla\Web\ThemedPage;
use VanillaTests\Fixtures\MockSiteMetaExtra;

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
        Timers::instance()->setShouldRecordProfile(false);
        return $this;
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

    /**
     * Override to log a dashboard access event.
     *
     * @return Data
     */
    public function render(): Data
    {
        $accessEvent = new DashboardAccessEvent();
        AuditLogger::log($accessEvent);
        $this->addSiteMetaExtra($accessEvent->asSiteMetaExtra());
        return parent::render();
    }
}
