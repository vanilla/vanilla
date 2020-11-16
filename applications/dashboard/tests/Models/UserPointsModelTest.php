<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Dashboard\Models;

use Vanilla\CurrentTimeStamp;
use Vanilla\Dashboard\UserPointsModel;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\SiteTestCase;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Tests for the user points model.
 */
class UserPointsModelTest extends SiteTestCase {

    use UsersAndRolesApiTestTrait;
    use CommunityApiTestTrait;

    /** @var UserPointsModel */
    private $userPointsModel;

    /** @var \CategoryModel */
    private $categoryModel;

    /**
     * @inheritdoc
     */
    public function setUp(): void {
        parent::setUp();
        $this->userPointsModel = $this->container()->get(UserPointsModel::class);
        $this->categoryModel = $this->container()->get(\CategoryModel::class);
        $this->resetTable('UserPoints');
    }

    /**
     * Test simple.
     */
    public function testSimpleLeaders() {
        $user1 = $this->createUser(['name' => 'User 1']);
        $user2 = $this->createUser(['name' => 'User 2']);
        $user3 = $this->createUser(['name' => 'No Points']);

        $this->givePoints($user1, 40);
        $this->givePoints($user2, 100);

        $leaders = $this->userPointsModel->getLeaders(UserPointsModel::SLOT_TYPE_WEEK);
        $this->assertArraySubsetRecursive([
            ['Name' => 'User 2'],
            ['Name' => 'User 1'],
        ], $leaders);
    }

    /**
     * Test leaders for a specific category.
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

        $leaders = $this->userPointsModel->getLeaders(UserPointsModel::SLOT_TYPE_WEEK, $cat1['categoryID']);
        $this->assertArraySubsetRecursive([
            ['Name' => 'Cat1User'],
        ], $leaders);
    }

    /**
     * Test exclusions based on permissions.
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
        \Gdn::config()->saveToConfig(UserPointsModel::CONF_EXCLUDE_PERMISSIONS, 'Garden.Settings.Manage');
        $this->assertCount(1, $this->userPointsModel->getLeaders(UserPointsModel::SLOT_TYPE_WEEK));

        \Gdn::config()->saveToConfig(UserPointsModel::CONF_EXCLUDE_PERMISSIONS, 'Garden.Moderation.Manage');
        $this->assertCount(0, $this->userPointsModel->getLeaders(UserPointsModel::SLOT_TYPE_WEEK));
    }

    /**
     * Test the different timeslots.
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

        $this->assertCount(1, $this->userPointsModel->getLeaders(UserPointsModel::SLOT_TYPE_WEEK));
        $this->assertCount(2, $this->userPointsModel->getLeaders(UserPointsModel::SLOT_TYPE_MONTH));
        $this->assertCount(3, $this->userPointsModel->getLeaders(UserPointsModel::SLOT_TYPE_YEAR));
        $this->assertCount(4, $this->userPointsModel->getLeaders(UserPointsModel::SLOT_TYPE_ALL));
    }
}
