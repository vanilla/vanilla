<?php
/**
 * @copyright 2008-2022 Vanilla Forums, Inc.
 * @license Proprietary
 */

namespace Vanilla\Dashboard\Modules;

use Vanilla\Dashboard\UserLeaderService;
use VanillaTests\SiteTestCase;
use VanillaTests\UsersAndRolesApiTestTrait;
use VanillaTests\Layout\LayoutTestTrait;

/**
 * Test CommunityLeadersModule title.
 */
class CommunityLeadersModuleTest extends SiteTestCase
{
    use UsersAndRolesApiTestTrait;
    use LayoutTestTrait;

    /**
     * Test that leaderboard widget takes custom title if its set and default one if its not.
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
     * Test that we can hydrate Leaderboard Widget.
     */
    public function testHydrateLeaderBoardWidget()
    {
        $this->resetTable("User");
        $user = $this->createUser(["name" => "User1"]);
        $this->givePoints($user, 20);
        $spec = [
            '$hydrate' => "react.leaderboard",
            "apiParams" => [
                "slotType" => "w",
                "limit" => 10,
                "leaderboardType" => UserLeaderService::LEADERBOARD_TYPE_REPUTATION,
                "includedRoleIDs" => [],
                "excludedRoleIDs" => [],
            ],
            "title" => "Leaderboard Title",
            "subtitle" => "Leaderboard Subtitle",
            "containerOptions" => [
                "borderType" => "shadow",
            ],
        ];
        $expected = [
            '$reactComponent' => "LeaderboardWidget",
            '$reactProps' => [
                "title" => $spec["title"],
                "subtitle" => $spec["subtitle"],
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
                            "punished" => 0, //this arrives after UserFragmentSchema::normalizeUserFragment() and did not exist in our $user
                            "private" => $user["private"],
                        ],
                        "points" => 20,
                    ],
                ],
            ],
        ];
        $this->assertHydratesTo($spec, [], $expected);
    }
}
