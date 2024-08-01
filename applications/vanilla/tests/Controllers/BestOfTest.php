<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\Forum\Controllers;

use VanillaTests\APIv0\TestDispatcher;
use VanillaTests\SiteTestCase;

/**
 * Tests for the /bestof page.
 */
class BestOfTest extends SiteTestCase
{
    /**
     * Test defaulting behaviour of the data driven themes on the bestof layout.
     */
    public function testBestOfDefaultView()
    {
        \Gdn::themeFeatures()->forceFeatures(["DataDrivenTheme" => true]);
        $this->assertBestOfIsList();

        // Still respects config if explicitly set.
        $this->runWithConfig(["Vanilla.Reactions.BestOfStyle" => "Tiles"], [$this, "assertBestOfIsTiles"]);

        \Gdn::themeFeatures()->forceFeatures(["DataDrivenTheme" => false]);
        $this->assertBestOfIsTiles();

        // Still respects config if explicitly set.
        $this->runWithConfig(["Vanilla.Reactions.BestOfStyle" => "List"], [$this, "assertBestOfIsList"]);
    }

    /**
     * Assert that bestof is currently rendering as a list.
     */
    public function assertBestOfIsList()
    {
        $html = $this->bessy()->getHtml("/bestof", [
            TestDispatcher::OPT_DELIVERY_TYPE => DELIVERY_TYPE_ALL,
        ]);
        $html->assertCssSelectorNotExists(".BestOfWrap > .Tiles", "BestOf should be a list.");
        $html->assertCssSelectorExists(".BestOfData > .DataList", "BestOf should be a list.");

        // This might have been dynamically set.
        \Gdn::config()->remove("Vanilla.Reactions.BestOfStyle", false);
    }

    /**
     * Assert that bestof is currently rendering as tiles.
     */
    public function assertBestOfIsTiles()
    {
        $html = $this->bessy()->getHtml("/bestof", [
            TestDispatcher::OPT_DELIVERY_TYPE => DELIVERY_TYPE_ALL,
        ]);
        $html->assertCssSelectorExists(".BestOfWrap > .Tiles", "BestOf should be tiles.");
        $html->assertCssSelectorNotExists(".BestOfData > .DataList", "BestOf should be tiles.");

        // This might have been dynamically set.
        \Gdn::config()->remove("Vanilla.Reactions.BestOfStyle", false);
    }
}
