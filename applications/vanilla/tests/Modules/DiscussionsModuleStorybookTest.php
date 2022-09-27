<?php
/**
 * @author Raphaël Bergina <raphael.bergina@vanillaforums.com>
 * @copyright 2008-2021 Vanilla Forums, Inc.
 * @license Proprietary
 */

namespace VanillaTests\Forum\Modules;

use Vanilla\Community\BaseDiscussionWidgetModule;
use Vanilla\Forum\Modules\DiscussionWidgetModule;
use VanillaTests\CategoryAndDiscussionApiTestTrait;
use VanillaTests\EventSpyTestTrait;
use VanillaTests\Storybook\StorybookGenerationTestCase;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Test rendering of the "Discussions" module.
 */
class DiscussionsModuleStorybookTest extends StorybookGenerationTestCase
{
    use EventSpyTestTrait;
    use CategoryAndDiscussionApiTestTrait;
    use UsersAndRolesApiTestTrait;

    private $categories = [];
    private $discussions = [];

    public static $addons = ["vanilla"];

    /**
     * Configure the container.
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->createData();
    }

    /**
     * Test rendering of the Call To Action module.
     */
    public function testRender()
    {
        $this->generateStoryHtml("/", "Discussions Widget Module");
    }

    /**
     * Event handler to mount Discussions module.
     *
     * @param \Gdn_Controller $sender
     */
    public function base_render_before(\Gdn_Controller $sender)
    {
        /** @var DiscussionWidgetModule $module */
        $module1 = self::container()->get(DiscussionWidgetModule::class);
        $module1->setTitle("Discussions Widget Module");
        $sender->addModule($module1);
    }

    /**
     * Create some discussions and categories for the module.
     */
    public function createData()
    {
        $this->resetTable("Category");
        $this->resetTable("Discussion");
        // Create categoryID = 1 & associated discussions
        $this->categories[] = $this->createCategory();
        $this->discussions[] = $this->createDiscussion(["name" => "test 1"]);
        $this->discussions[] = $this->createDiscussion(["name" => "test 2"]);
        $this->discussions[] = $this->createDiscussion(["name" => "test 3"]);
        $this->discussions[] = $this->createDiscussion(["name" => "test 4"]);

        // Create categoryID = 2 & associated discussions
        $this->categories[] = $this->createCategory();
        $this->discussions[] = $this->createDiscussion(["name" => "test 5", "categoryID" => 2]);
        $this->discussions[] = $this->createDiscussion(["name" => "test 6", "categoryID" => 2]);
        $this->discussions[] = $this->createDiscussion(["name" => "test 7", "categoryID" => 2]);
        $this->discussions[] = $this->createDiscussion(["name" => "test 8", "categoryID" => 2]);
    }

    /**
     * Test returning discussions from a followed category with a user that doesn't follow any category.
     */
    public function testUserNotFollowingCategory()
    {
        $userID = \Gdn::session()->UserID;

        // We unfollow every category
        foreach ($this->categories as $category) {
            \Gdn::getContainer()
                ->get(\CategoryModel::class)
                ->follow($userID, $category["categoryID"], false);
        }

        $widget = \Gdn::getContainer()->get(BaseDiscussionWidgetModule::class);

        // We want the widget to provide the followed categories
        $widget->setApiParams(["followed" => true]);
        $props = $widget->getProps();

        // No discussions were returned
        $this->assertNull($props);
    }

    /**
     * Test returning discussions from a followed category with a user that follows one category.
     */
    public function testUserFollowingCategory()
    {
        $userID = \Gdn::session()->UserID;
        $followedCategoryID = 2;

        // We follow category 2.
        \Gdn::getContainer()
            ->get(\CategoryModel::class)
            ->follow($userID, $followedCategoryID, true);

        $widget = \Gdn::getContainer()->get(BaseDiscussionWidgetModule::class);

        // We want the widget to provide the followed categories.
        $widget->setApiParams(["followed" => true]);
        $discussions = $widget->getProps()["discussions"];

        // Every returned discussion belongs to category 2.
        foreach ($discussions as $discussion) {
            $this->assertEquals($followedCategoryID, $discussion["categoryID"]);
        }
    }

    /**
     * Test returning discussions from a followed category with a user that isn't signed in.
     */
    public function testNotSignedInUserFollowingCategory()
    {
        $userID = \UserModel::GUEST_USER_ID;

        $this->runWithUser(function () use ($userID) {
            $widget = \Gdn::getContainer()->get(BaseDiscussionWidgetModule::class);

            // We want the widget to provide the followed categories
            $widget->setApiParams(["followed" => true]);
            $props = $widget->getProps();

            // No discussions were returned
            $this->assertNull($props);
        }, $userID);
    }
}
