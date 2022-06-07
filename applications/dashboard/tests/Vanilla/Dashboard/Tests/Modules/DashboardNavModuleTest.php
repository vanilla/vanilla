<?php
/**
 * @author Isis Graziatto <igraziatto@higherlogic.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Tests\Modules;

use VanillaTests\APIv2\AbstractAPIv2Test;

/**
 * Test converge for the DashboardNavModule.
 */
class DashboardNavModuleTest extends AbstractAPIv2Test
{
    /** @var \DashboardNavModule */
    private $nav;

    /**
     * @inheridoc
     */
    public function setUp(): void
    {
        parent::setUp();
        /** @var \DashboardNavModule dashboardNavModule */
        $this->nav = \DashboardNavModule::getDashboardNav();
        $this->session = \Gdn::session();
    }

    /**
     * Asset retrieving sections with preferences set and without.
     */
    public function testUserPreferencesLandingPage()
    {
        $preference = ["Settings" => "/vanilla/settings/posting"];
        $this->session->setPreference("DashboardNav.SectionLandingPages", $preference);

        $sectionInfo = $this->nav->getSectionsInfo();
        $this->assertSame($preference["Settings"], $sectionInfo["Settings"]["url"]);

        //reset section
        $section = [
            "permission" => ["Garden.Settings.Manage", "Garden.Community.Manage"],
            "section" => "Settings",
            "title" => "Settings",
            "description" => "Configuration & Addons",
            "url" => "/dashboard/role",
        ];
        $this->nav->registerSection($section);

        $sectionInfo = $this->nav->getSectionsInfo(false);
        $this->assertSame($section["url"], $sectionInfo["Settings"]["url"]);
    }
}
