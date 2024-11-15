<?php
/**
 * @author Isis Graziatto <igraziatto@higherlogic.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Forum\Widgets;

use Vanilla\Community\UserSpotlightModule;
use Vanilla\Models\UserFragmentSchema;
use Vanilla\Utility\ArrayUtils;
use VanillaTests\EventSpyTestTrait;
use VanillaTests\Layout\LayoutTestTrait;
use VanillaTests\Storybook\StorybookGenerationTestCase;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Tests covering the UserSpotlightModule and the UserSpotlightWidget.
 */
class UserSpotlightWidgetStorybookTest extends StorybookGenerationTestCase
{
    use LayoutTestTrait;
    use UsersAndRolesApiTestTrait;
    use EventSpyTestTrait;

    public static $addons = ["vanilla"];

    /** @var string */
    private $descriptionStub = "Mauris eu volutpat nibh. Nam non nulla vel massa congue rutrum. Nullam arcu mi, aliquet sed malesuada condimentum, bibendum at urna.";

    /**
     * Test rendering of the UserSpotlightModule.
     */
    public function testRender()
    {
        $this->generateStoryHtml("/", "User Spotlight Module");
    }

    /**
     * Event handler to mount Discussions module.
     *
     * @param \Gdn_Controller $sender
     */
    public function base_render_before(\Gdn_Controller $sender)
    {
        $user = $this->createUser();
        /** @var UserSpotlightModule $module */
        $module = self::container()->get(UserSpotlightModule::class);
        $module->setTitle("User Spotlight Module");
        $module->setDescription($this->descriptionStub);
        $module->setUserID($user["userID"]);
        $sender->addModule($module);
    }

    /**
     * Test hydrating the UserSpotlightWidget.
     */
    public function testHydrateUserSpotlightWidget()
    {
        $user = $this->createUser(["name" => "my-user"]);
        $apiParams = [
            "userID" => $user["userID"],
        ];
        $containerOptions = [
            "borderType" => "shadow",
            "visualBackgroundType" => "inner",
        ];
        $spec = [
            '$hydrate' => "react.userspotlight",
            "title" => "Our top member of the month",
            "titleType" => "static",
            "descriptionType" => "static",
            "description" => $this->descriptionStub,
            "apiParams" => $apiParams,
            "containerOptions" => $containerOptions,
            '$reactTestID' => "userspotlight",
        ];
        $expected = [
            '$reactComponent' => "UserSpotlightWidget",
            '$reactProps' => [
                "title" => "Our top member of the month",
                "titleType" => "static",
                "descriptionType" => "static",
                "description" => $this->descriptionStub,
                "apiParams" => $apiParams,
                "containerOptions" => $containerOptions,
                "userTextAlignment" => "left",
                "userInfo" => ArrayUtils::pluck($user, UserFragmentSchema::schemaProperties()),
            ],
            '$reactTestID' => "userspotlight",
            '$seoContent' => <<<HTML
<div class=pageBox>
    <div class=pageHeadingBox>
        <h2>Our top member of the month</h2>
        <p>Mauris eu volutpat nibh. Nam non nulla vel massa congue rutrum. Nullam arcu mi, aliquet sed malesuada condimentum, bibendum at urna.</p>
    </div>
    <div>
        <a class=seoUser href={$user["url"]}>
            <img alt="Photo of my-user" height=24px src={$user["photoUrl"]} width=24px>
            <span class=seoUserName>my-user</span>
        </a>
    </div>
</div>
HTML
        ,
        ];

        $this->assertHydratesTo($spec, [], $expected);
    }
}
