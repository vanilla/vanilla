<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Vanilla\Controllers;

use VanillaTests\ExpectExceptionTrait;
use VanillaTests\SiteTestCase;

/**
 * Tests for the /vanilla/settings/posting page.
 */
class PostingSettingsTest extends SiteTestCase
{
    use ExpectExceptionTrait;

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
     * Test validation on saving max title length.
     *
     * @return void
     */
    public function testValidateTitleMaxLength()
    {
        $this->runWithExpectedExceptionMessage("The max post title length must be between 50 and 250.", function () {
            $postingFormValues = $this->getValidSettingsPayload();
            $postingFormValues["Vanilla.Discussion.Title.MaxLength"] = 40;
            $this->bessy()->post("/vanilla/settings/posting", $postingFormValues);
        });
        $this->runWithExpectedExceptionMessage("The max post title length must be between 50 and 250.", function () {
            $postingFormValues = $this->getValidSettingsPayload();
            $postingFormValues["Vanilla.Discussion.Title.MaxLength"] = 300;
            $this->bessy()->post("/vanilla/settings/posting", $postingFormValues);
        });

        $postingFormValues = $this->getValidSettingsPayload();
        $postingFormValues["Vanilla.Discussion.Title.MaxLength"] = 200;
        $this->bessy()->post("/vanilla/settings/posting", $postingFormValues);
    }

    /**
     * Get an example posting settings payload.
     *
     * @return array
     */
    private function getValidSettingsPayload()
    {
        return [
            "Vanilla.Comment.MaxLength" => 100,
            "Garden.InputFormatter" => "rich",
            "Garden.MobileInputFormatter" => "rich",
            "Vanilla.Discussions.PerPage" => 10,
            "Vanilla.Comments.PerPage" => 10,
            "Vanilla.Discussion.Title.MaxLength" => 100,
        ];
    }
}
