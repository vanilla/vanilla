<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Vanilla\Controllers;

use VanillaTests\SiteTestCase;

/**
 * Tests for the /vanilla/settings/posting page.
 */
class PostingSettingsTest extends SiteTestCase
{
    public static $addons = ["rich-editor", "editor"];

    /**
     * On a brand new site, rich2 is the new posting format and rich1 is not available
     */
    public function testRichIncludedButIsRich2()
    {
        $html = $this->bessy()->getHtml("/vanilla/settings/posting");

        $html->assertCssSelectorText("select[name='Garden-dot-InputFormatter'] option[value='Rich2']", "Rich");
        $html->assertCssSelectorText("select[name='Garden-dot-MobileInputFormatter'] option[value='Rich2']", "Rich");
    }

    /**
     * If rich 1 is enabled rich1 and rich2 still appear in the dropdown.
     */
    public function testRich1Holdover()
    {
        \Gdn::config()->saveToConfig([
            "Garden.InputFormatter" => "Rich",
        ]);
        $html = $this->bessy()->getHtml("/vanilla/settings/posting");

        $html->assertCssSelectorText("select[name='Garden-dot-InputFormatter'] option[value='Rich2']", "Rich2");
        $html->assertCssSelectorText("select[name='Garden-dot-InputFormatter'] option[value='Rich']", "Rich");
        $html->assertCssSelectorText("select[name='Garden-dot-MobileInputFormatter'] option[value='Rich2']", "Rich2");
        $html->assertCssSelectorText("select[name='Garden-dot-MobileInputFormatter'] option[value='Rich']", "Rich");
    }
}
