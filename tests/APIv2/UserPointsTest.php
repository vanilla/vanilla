<?php
/**
* @copyright 2009-2022 Vanilla Forums Inc.
* @license GPL-2.0-only
*/

namespace VanillaTests\APIv2;

use Vanilla\CurrentTimeStamp;
use Vanilla\Dashboard\UserLeaderService;
use Vanilla\Dashboard\UserPointsModel;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\SiteTestCase;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Test the /api/v2/users endpoints related to points.
 */
class UserPointsTest extends SiteTestCase {

    use UsersAndRolesApiTestTrait;
    use CommunityApiTestTrait;

    /** @var UserPointsModel */
    private $userPointsModel;

    /** @var \CategoryModel */
    private $categoryModel;

    public static $addons = ["vanillaanalytics"];
    /**
     * Disable email before running tests.
     */
    public function setUp(): void {
        parent::setUp();
        $this->userPointsModel = $this->container()->get(UserPointsModel::class);
        $this->categoryModel = $this->container()->get(\CategoryModel::class);
        $this->resetTable('UserPoints');
        \Gdn::cache()->flush();
    }

    /**
     * Simple test for the `/users/leaders` API endpoint.
     */
    public function testSimpleLeaders() {
        $user1 = $this->createUser(['name' => 'User 1']);
        $user2 = $this->createUser(['name' => 'User 2']);
        $user3 = $this->createUser(['name' => 'No Points']);

        $this->givePoints($user1, 40);
        $this->givePoints($user2, 100);

        $leaders = $this->api()->get(
            "users/leaders",
            ["slotType" => UserPointsModel::SLOT_TYPE_WEEK, "leaderboardType" => UserLeaderService::LEADERBOARD_TYPE_REPUTATION]
        )->getBody();

        $this->assertArraySubsetRecursive([
            ['name' => 'User 2'],
            ['name' => 'User 1'],
        ], $leaders);
    }

    /**
     * Test leaders for a specific category using the `/users/leaders` API endpoint.
     */
    public function testCategoryLeaders() {
        $cat1 = $this->createCategory();
        $cat2 = $this->createCategory();

        $this->categoryModel->setField($cat1['categoryID'], 'PointsCategoryID', $cat1['categoryID']);
        $this->categoryModel->setField($cat2['categoryID'], 'PointsCategoryID', $cat2['categoryID']);

        $globalUser = $this->createUser(['name' => 'GlobalUser']);
        $cat1User = $this->createUser(['name' => 'Cat1User']);
        $cat2User = $this->createUser(['name' => 'Cat2User']);

        $this->givePoints($cat1User, 10, $cat1);
        $this->givePoints($cat2User, 10, $cat2);
        $this->givePoints($globalUser, 10);

        $leaders = $this->api()->get(
            "users/leaders",
            [
                "leaderboardType" => UserLeaderService::LEADERBOARD_TYPE_REPUTATION,
                "slotType" => UserPointsModel::SLOT_TYPE_WEEK,
                "categoryID" => $cat1['categoryID']
            ]
        )->getBody();

        $this->assertArraySubsetRecursive([
            ['name' => 'Cat1User'],
        ], $leaders);
    }

    /**
     * Test exclusions based on permissions for the `/users/leaders` API endpoint.
     */
    public function testExclusions() {
        $adminRole = $this->createRole([
            'name' => 'SuperAdmin',
            'permissions' => [
                [
                    'type' => 'global',
                    'permissions' => [
                        'site.manage' => true,
                    ],
                ],
            ]
        ]);
        $moderator = $this->createRole([
            'name' => 'SuperAdmin',
            'permissions' => [
                [
                    'type' => 'global',
                    'permissions' => [
                        'community.moderate' => true,
                    ],
                ],
            ]
        ]);
        $admin = $this->createUser(['name' => 'Admin', 'roleID' => [$adminRole['roleID']]]);
        $moderator = $this->createUser(['name' => 'Moderator', 'roleID' => [$moderator['roleID']]]);

        $this->givePoints($admin, 100000);
        $this->givePoints($moderator, 100000);

        // The level at which you want to start excluding admins
        \Gdn::config()->saveToConfig(UserLeaderService::CONF_EXCLUDE_PERMISSIONS, 'Garden.Settings.Manage');
        $leaders = $this->api()->get(
            "users/leaders",
            [
                "slotType" => UserPointsModel::SLOT_TYPE_WEEK,
                "leaderboardType" => UserLeaderService::LEADERBOARD_TYPE_REPUTATION
            ]
        )->getBody();
        $this->assertCount(1, $leaders);

        \Gdn::config()->saveToConfig(UserLeaderService::CONF_EXCLUDE_PERMISSIONS, 'Garden.Moderation.Manage');
        $leaders = $this->api()->get(
            "users/leaders",
            [
                "slotType" => UserPointsModel::SLOT_TYPE_WEEK,
                "leaderboardType" => UserLeaderService::LEADERBOARD_TYPE_REPUTATION
            ]
        )->getBody();
        $this->assertCount(0, $leaders);
    }

    /**
     * Test the different timeslots for the `/users/leaders` API endpoint.
     */
    public function testSlotTypeLeaders() {
        $userNow = $this->createUser(['name' => 'Now']);
        $user2Weeks = $this->createUser(['name' => '2 weeks']);
        $user2Months = $this->createUser(['name' => '2 months']);
        $user2Years = $this->createUser(['name' => '2 years']);


        CurrentTimeStamp::mockTime("Oct 1 2020");
        $this->givePoints($user2Weeks, 100);

        CurrentTimeStamp::mockTime("Aug 1 2020");
        $this->givePoints($user2Months, 100);

        CurrentTimeStamp::mockTime("Oct 15 2019");
        $this->givePoints($user2Years, 100);

        CurrentTimeStamp::mockTime("Oct 15 2020");
        $this->givePoints($userNow, 100);

        $weeklyLeaders = $this->api()->get(
            "users/leaders",
            [
                "slotType" => UserPointsModel::SLOT_TYPE_WEEK,
                "leaderboardType" => UserLeaderService::LEADERBOARD_TYPE_REPUTATION
            ]
        )->getBody();
        $monthlyLeaders = $this->api()->get(
            "users/leaders",
            [
                "slotType" => UserPointsModel::SLOT_TYPE_MONTH,
                "leaderboardType" => UserLeaderService::LEADERBOARD_TYPE_REPUTATION
            ]
        )->getBody();
        $yearlyLeaders = $this->api()->get(
            "users/leaders",
            [
                "slotType" => UserPointsModel::SLOT_TYPE_YEAR,
                "leaderboardType" => UserLeaderService::LEADERBOARD_TYPE_REPUTATION
            ]
        )->getBody();
        $allTimeLeaders = $this->api()->get(
            "users/leaders",
            [
                "slotType" => UserPointsModel::SLOT_TYPE_ALL,
                "leaderboardType" => UserLeaderService::LEADERBOARD_TYPE_REPUTATION
            ]
        )->getBody();

        $this->assertCount(1, $weeklyLeaders);
        $this->assertCount(2, $monthlyLeaders);
        $this->assertCount(3, $yearlyLeaders);
        $this->assertCount(4, $allTimeLeaders);
    }

    /**
     * Test leader count after banning user using the `/users/leaders` API endpoint.
     */
    public function testGetLeadersBanned() {
        $userBanned = $this->createUser(['name' => 'banned']);
        $this->givePoints($userBanned, 100000);

        $leaders = $this->api()->get(
            "users/leaders",
            [
                "slotType" => UserPointsModel::SLOT_TYPE_ALL,
                "leaderboardType" => UserLeaderService::LEADERBOARD_TYPE_REPUTATION
            ]
        )->getBody();
        $this->assertCount(1, $leaders);

        $this->userModel->ban($userBanned['userID'], ['Reason' => 'testBan']);
        \Gdn::cache()->flush();
        $leaders = $this->api()->get(
            "users/leaders",
            [
                "slotType" => UserPointsModel::SLOT_TYPE_ALL, "leaderboardType" => UserLeaderService::LEADERBOARD_TYPE_REPUTATION
            ]
        )->getBody();
        $this->assertCount(0, $leaders);
    }

    /**
     * Test leader count after deleting a user using the `/users/leaders` API endpoint.
     */
    public function testGetLeadersDeleted() {
        $userDeleted = $this->createUser(['name' => 'deleted']);
        $this->givePoints($userDeleted, 100000);
        $leaders = $this->api()->get(
            "users/leaders",
            [
                "slotType" => UserPointsModel::SLOT_TYPE_ALL,
                "leaderboardType" => UserLeaderService::LEADERBOARD_TYPE_REPUTATION
            ]
        )->getBody();
        $this->assertCount(1, $leaders);

        $this->userModel->deleteID($userDeleted['userID']);
        \Gdn::cache()->flush();
        $leaders = $this->api()->get(
            "users/leaders",
            [
                "slotType" => UserPointsModel::SLOT_TYPE_ALL,
                "leaderboardType" => UserLeaderService::LEADERBOARD_TYPE_REPUTATION
            ]
        )->getBody();
        $this->assertCount(0, $leaders);
    }
}
