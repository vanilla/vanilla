<?php
/**
 * @copyright 2008-2023 Vanilla Forums, Inc.
 * @license Proprietary
 */

namespace VanillaTests\Forum\Widgets;

use Vanilla\Dashboard\Modules\CommunityLeadersModule;
use Vanilla\Dashboard\UserLeaderService;
use Vanilla\Dashboard\UserPointsModel;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\SiteTestCase;
use VanillaTests\UsersAndRolesApiTestTrait;
use VanillaTests\Layout\LayoutTestTrait;
use CategoryModel;

/**
 * Test CommunityLeadersModule title.
 */
class LeaderboardWidgetTest extends SiteTestCase
{
    use UsersAndRolesApiTestTrait;
    use LayoutTestTrait;
    use CommunityApiTestTrait;

    public static $addons = ["subcommunities"];

    /**
     * Test that the leaderboard widget takes a custom title if it's set or defaults otherwise.
     */
    public function testCommunityLeadersTitle()
    {
        $user = $this->createUser(["name" => "testUser"]);
        $this->givePoints($user, 40);

        /** @var CommunityLeadersModule $widgetModule */
        $widgetModule = self::container()->get(CommunityLeadersModule::class);
        $props = $widgetModule->getProps();

        //default title
        $this->assertEquals("This Week's Leaders", $props["title"]);

        $widgetModule->title = "Custom title";
        $newProps = $widgetModule->getProps();

        //should be our custom title now
        $this->assertEquals("Custom title", $newProps["title"]);
    }

    /**
     * Test the default leaderboard options are available
     */
    public function testDefaultLeaderboardTypeOptions()
    {
        $schema = UserPointsModel::leaderboardTypeSchema();
        $staticOptions = $schema["x-control"]["options"];

        $this->assertEquals(
            [["value" => UserLeaderService::LEADERBOARD_TYPE_REPUTATION, "label" => "Reputation points"]],
            $staticOptions
        );
    }

    /**
     * Test the analytics driven leaderboard options are available
     */
    public function testLeaderboardTypeOptionsWithAnalytics()
    {
        $this->enableAddon("vanillaanalytics");
        $schema = UserPointsModel::leaderboardTypeSchema();
        $staticOptions = $schema["x-control"]["options"];

        $this->assertEquals(
            [
                [
                    "label" => "Reputation points",
                    "value" => UserLeaderService::LEADERBOARD_TYPE_REPUTATION,
                ],
                [
                    "label" => "Accepted answers count",
                    "value" => UserLeaderService::LEADERBOARD_TYPE_ACCEPTED_ANSWERS,
                ],
            ],
            $staticOptions
        );
    }

    /**
     * Test that we can hydrate Leaderboard Widget.
     */
    public function testHydrateLeaderBoardWidget()
    {
        $this->resetTable("User");
        $user = $this->createUser(["name" => "User1"]);

        /** @var CategoryModel $categoryModel */
        $categoryModel = self::container()->get("CategoryModel");
        // Create a category with custom points.
        $categoryID = $categoryModel->save([
            "Name" => "Category with custom points",
            "UrlCode" => "category-with-custom-points",
        ]);

        $this->givePoints($user, 20);
        $this->givePoints($user, 10, $categoryID);

        $spec = [
            '$hydrate' => "react.leaderboard",
            "apiParams" => [
                "slotType" => "w",
                "limit" => 10,
                "leaderboardType" => UserLeaderService::LEADERBOARD_TYPE_REPUTATION,
                "includedRoleIDs" => [],
                "excludedRoleIDs" => [],
                "filter" => "none",
                "siteSectionID" => null,
                "includeChildCategories" => false,
            ],
            "title" => "Leaderboard Title",
            "titleType" => "static",
            "subtitle" => "Leaderboard Subtitle",
            "containerOptions" => [
                "borderType" => "shadow",
                "headerAlignment" => "left",
                "visualBackgroundType" => "inner",
            ],
            '$reactTestID' => "leaderboard",
            "descriptionType" => "none",
        ];
        $expected = [
            '$reactComponent' => "LeaderboardWidget",
            '$reactProps' => [
                "title" => $spec["title"],
                "titleType" => "static",
                "subtitle" => $spec["subtitle"],
                "descriptionType" => "none",
                "containerOptions" => $spec["containerOptions"],
                "apiParams" => $spec["apiParams"],
                "leaders" => [
                    [
                        "user" => [
                            "userID" => $user["userID"],
                            "name" => $user["name"],
                            "url" => $user["url"],
                            "photoUrl" => $user["photoUrl"],
                            "dateLastActive" => null, // UserFragmentSchema::normalizeUserFragment() returns null for this one
                            "banned" => $user["banned"],
                            "private" => $user["private"],
                        ],
                        "points" => 30, //total points
                    ],
                ],
            ],
            '$reactTestID' => "leaderboard",
            '$seoContent' => <<<HTML
<div class=pageBox>
    <div class=pageHeadingBox>
        <h2>Leaderboard Title</h2>
        <h3>Leaderboard Subtitle</h3>
    </div>
    <div class=row>
        <a class=seoUser href={$user["url"]}>
            <img alt="Photo of User1" height=24px src={$user["photoUrl"]} width=24px>
            <span class=seoUserName>User1</span>
        </a>
        <span>30</span>
    </div>
</div>
HTML
        ,
        ];
        $this->assertHydratesTo($spec, [], $expected);

        //let's tweak the spec bit to add a filter by category
        $spec2 = $spec;
        $spec2["apiParams"]["filter"] = "category";
        $spec2["apiParams"]["filterCategorySubType"] = "set";
        $spec2["apiParams"]["categoryID"] = $categoryID;

        $expected2 = $expected;
        $expected2['$reactProps']["apiParams"]["filter"] = "category";
        $expected2['$reactProps']["apiParams"]["categoryID"] = $categoryID;
        $expected2['$reactProps']["leaders"][0]["points"] = 10; // only points in from the category

        unset($expected2['$reactProps']["apiParams"]["siteSectionID"]);
        unset($expected2['$seoContent']);

        $this->assertHydratesTo($spec2, [], $expected2);
        $this->assertTrue(true);
    }
}
