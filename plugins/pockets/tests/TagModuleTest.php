<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Modules;

use Gdn;
use Vanilla\Addons\Pockets\PocketsModel;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\SiteTestCase;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Tests for the TagModule
 */
class TagModuleTest extends SiteTestCase
{
    use UsersAndRolesApiTestTrait, CommunityApiTestTrait;

    public static $addons = ["pockets"];

    /**
     * {@inheritDoc}
     */
    public function setUp(): void
    {
        $config = $this->container()->get(\Gdn_Configuration::class);
        $config->saveToConfig("Tagging.Discussions.Enabled", true);
        parent::setUp();
    }

    /**
     * Test that an error message is logged when the tag module's view is not found.
     */
    public function testTagModuleOnPage(): void
    {
        $tagPocket = [
            "Checkboxes" => [
                0 => "Enabled",
                1 => "MobileOnly",
                2 => "MobileNever",
                3 => "EmbeddedNever",
                4 => "ShowInDashboard",
                5 => "Ad",
                6 => "TestMode",
            ],
            "Enabled" => "1",
            "Name" => "Tag Cloud",
            "Format" => "widget",
            "WidgetID" => "tag-cloud",
            "WidgetParameters" => "[]",
            "Location" => "Panel",
            "Page" => "",
            "RoleIDs" => "",
            "CategoryID" => "",
            "InheritCategory" => "0",
            "RepeatType" => "before",
            "EveryFrequency" => "",
            "EveryBegin" => "",
            "Indexes" => "",
            "Save" => "Save",
            "MobileOnly" => false,
            "MobileNever" => false,
            "EmbeddedNever" => false,
            "ShowInDashboard" => false,
            "Ad" => false,
            "TestMode" => false,
            "Repeat" => "before",
            "Sort" => 0,
            "Condition" => "",
            "Type" => "default",
            "Disabled" => 0,
        ];
        $tag = $this->createTag();
        $discussion = $this->createDiscussion();
        $this->api()->put("/discussions/{$discussion["discussionID"]}/tags", ["tagIDs" => [$tag["tagID"]]]);
        $pocketModel = Gdn::getContainer()->get(PocketsModel::class);
        $pocketModel->save($tagPocket);

        // There's no view for the module on profile pages.
        $this->bessy()->getHtml("/profile", ["deliveryType" => DELIVERY_TYPE_ALL]);
        $message =
            "Could not find a `tag` view for the `TagModule` module in the `dashboard` application.|TagModule|FetchView|";
        $this->assertErrorLogMessage($message);
    }
}
