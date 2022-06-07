<?php
/**
 * @author Isis Graziatto <igraziatto@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Pages;

use DashboardNavModule;
use Garden\Web\Exception\ServerException;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Web\Page;

/**
 * Page that handles legacy dashboard pages (basically pages rendering from Gdn_Controllers)
 */
class LegacyDashboardPage extends Page
{
    /** @var string */
    const MASTER_VIEW_PATH = "addons/themes/theme-dashboard/views/admin.master.twig";

    /** @var ConfigurationInterface */
    private $config;

    /** @var \Gdn_Controller */
    private $controller;

    /**
     * @inheritdoc
     */
    public function initialize(\Gdn_Controller $controller = null)
    {
        if ($controller === null) {
            throw new ServerException("Unable to initialize page.");
        }

        $this->setSeoTitle("Dashboard")
            ->setSeoRequired(false)
            ->blockRobots()
            ->setController($controller)
            ->setConfig();
    }

    /**
     * Set Page config
     */
    private function setConfig()
    {
        $this->config = \Gdn::getContainer()->get("Config");
        return $this;
    }

    /**
     * Set Page controller
     *
     * @param \Gdn_Controller $controller
     * @return $this
     */
    private function setController(\Gdn_Controller $controller)
    {
        $this->controller = $controller;
        return $this;
    }

    /**
     * Get DashboardNavModule
     *
     * @param string $activeUrl
     * @return DashboardNavModule
     * @throws \Exception Throws an exception if the module isn't configured properly.
     */
    private function getDashboardNav($activeUrl): DashboardNavModule
    {
        /** @var DashboardNavModule $dashboardNav */
        $dashboardNav = DashboardNavModule::getDashboardNav();
        $dashboardNav->getSectionsInfo(false);
        $dashboardNav->setHighlightRoute($activeUrl);

        return $dashboardNav;
    }

    /**
     * Render the page as a string.
     * This method needed to be overwritten because Gdn_Controller can't dispatch Data
     */
    public function renderPage(): string
    {
        $viewData = [
            "locale" => $this->siteMeta->getLocaleKey(),
            "forumLinkLabel" => $this->config->get("Garden.Title"),
            "cssClass" => $this->controller->Data["CssClass"],
            "vanillaUrl" => $this->config->get("Garden.VanillaUrl"),
            "showVanillaVersion" => !\Gdn::addonManager()->isEnabled("vfoptions", \Vanilla\Addon::TYPE_ADDON),
            "applicationVersion" => APPLICATION_VERSION,
            "dashboardNav" => $this->getDashboardNav($this->controller->SelfUrl),
            "IsWidePage" => $this->controller->Data["IsWidePage"] ?? false,
        ];

        return $this->masterViewRenderer->renderPage($this, $viewData, self::MASTER_VIEW_PATH);
    }

    /**
     * @inheritdoc
     */
    public function getAssetSection(): string
    {
        return "admin";
    }
}
