<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Controllers\Pages;

use Garden\Hydrate\DataHydrator;
use Gdn;
use Vanilla\Layout\Asset\LayoutQuery;
use Vanilla\Layout\LayoutPage;
use Vanilla\Site\DefaultSiteSection;
use Vanilla\Site\SiteSectionModel;
use Vanilla\Utility\Timers;
use Vanilla\Web\PageDispatchController;

/**
 * Controller for the custom layout homepage.
 */
class HomePageController extends PageDispatchController
{
    /** @var SiteSectionModel */
    private $siteSectionModel;

    /**
     * DI.
     *
     * @param SiteSectionModel $siteSectionModel
     */
    public function __construct(SiteSectionModel $siteSectionModel)
    {
        $this->siteSectionModel = $siteSectionModel;
    }

    /**
     * Homepage index.
     */
    public function index()
    {
        $siteSection = $this->siteSectionModel->getCurrentSiteSection();
        $query = new LayoutQuery(
            $siteSection->getSectionID() !== DefaultSiteSection::DEFAULT_ID ? "subcommunityHome" : "home",
            "siteSection",
            (string) $siteSection->getSectionID(),
            [
                "siteSectionID" => (string) $siteSection->getSectionID(),
                "locale" => $siteSection->getContentLocale(),
            ]
        );

        return $this->usePage(LayoutPage::class)
            ->permission()
            ->setSeoRequired(false)
            ->preloadLayout($query)
            ->render();
    }
}
