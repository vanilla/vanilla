<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Tests\Controllers;

use CategoryModel;
use PHPUnit\Framework\TestCase;
use Vanilla\CurrentTimeStamp;
use Vanilla\Formatting\Formats\MarkdownFormat;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\Models\TestDiscussionModelTrait;
use VanillaTests\SetupTraitsTrait;
use VanillaTests\SiteTestTrait;
use VanillaTests\VanillaTestCase;

/**
 * Class DiscussionsControllerTest
 */
class DiscussionsControllerTest extends TestCase
{
    use SiteTestTrait, SetupTraitsTrait, CommunityApiTestTrait, TestDiscussionModelTrait;

    /** @var CategoryModel */
    private static $categoryModel;

    /**
     * @var array
     */
    private $discussion;

    /**
     * {@inheritDoc}
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->setupTestTraits();
        self::$categoryModel = self::container()->get(CategoryModel::class);
        $this->discussion = $this->insertDiscussions(1)[0];
    }

    /**
     * {@inheritDoc}
     */
    public function tearDown(): void
    {
        parent::tearDown();
        $this->tearDownTestTraits();
        $this->container()->setInstance(CategoryModel::class, null);
    }

    /**
     * Smoke test the /discussions endpoint.
     *
     * @return array
     */
    public function testRecentDiscussions(): array
    {
        $data = $this->bessy()->get("/discussions")->Data;
        $this->assertNotEmpty($data["Discussions"]);

        return $data;
    }

    /**
     * Smoke test the /discussions endpoint.
     *
     * @return array
     */
    public function testFollowedRecentDiscussions(): array
    {
        /** @var \Gdn_Configuration $config */
        $config = static::container()->get("Config");
        $config->set(\CategoryModel::CONF_CATEGORY_FOLLOWING, true, true, false);
        // follow a category
        self::$categoryModel->follow(\Gdn::session()->UserID, 1, true);
        $data = $this->bessy()->get("/discussions?followed=1")->Data;
        $this->assertNotEmpty($data["Discussions"]);

        return $data;
    }

    /**
     * Smoke test a basic discussion fetch.
     *
     * @param array $data
     * @depends testRecentDiscussions
     */
    public function testGetDiscussion(array $data): void
    {
        foreach ($data["Discussions"] as $discussion) {
            $url = discussionUrl($discussion, "", "/");
            $responseData = $this->bessy()->get($url)->Data;

            $this->assertArrayHasKey("Discussion", $responseData);
            $this->assertArrayHasKey("Comments", $responseData);

            break;
        }
    }

    /**
     * Test a basic announce happy path.
     */
    public function testAnnounce(): array
    {
        $this->assertEquals(0, $this->discussion["Announce"]);
        $id = $this->discussion["DiscussionID"];
        $data = $this->bessy()->post("/discussion/announce/$id", ["Announce" => 1])->Data;
        $discussion = $this->discussionModel->getID($id, DATASET_TYPE_ARRAY);
        $this->assertEquals(1, $discussion["Announce"]);

        return $discussion;
    }

    /**
     * Test `POST /discusion/bookmark/:id`.
     */
    public function testBookmark(): void
    {
        $id = $this->discussion["DiscussionID"];
        $data = $this->bessy()->post("/discussion/bookmark/$id", ["Bookmark" => 1])->Data;
        $this->assertTrue((bool) $data["Bookmarked"]);
        $discussions = $this->bessy()
            ->get("/discussions/bookmarked")
            ->data("Discussions")
            ->resultArray();
        $row = VanillaTestCase::assertDatasetHasRow($discussions, ["DiscussionID" => $id]);
        $this->assertTrue((bool) $row["Bookmarked"]);
    }

    /**
     * Test `/discussion/dismiss/:id`.
     */
    public function testDismiss(): void
    {
        $discussion = $this->testAnnounce();
        $id = $discussion["DiscussionID"];

        $data = $this->bessy()->post("/discussion/dismiss-announcement/$id");

        $discussions = $this->bessy()
            ->get("/discussions")
            ->data("Discussions")
            ->resultArray();
        $row = VanillaTestCase::assertDatasetHasRow($discussions, ["DiscussionID" => $id]);
        $this->assertTrue((bool) $row["Dismissed"]);
    }

    /**
     * Test sink happy path.
     */
    public function testSink(): void
    {
        $this->assertEquals(0, $this->discussion["Sink"]);
        $id = $this->discussion["DiscussionID"];
        $data = $this->bessy()->post("/discussion/sink/$id")->Data;
        $discussion = $this->discussionModel->getID($id, DATASET_TYPE_ARRAY);
        $this->assertEquals(1, $discussion["Sink"]);
    }

    /**
     * Test close happy path.
     */
    public function testClose(): void
    {
        $this->assertEquals(0, $this->discussion["Closed"]);
        $id = $this->discussion["DiscussionID"];
        $data = $this->bessy()->post("/discussion/close/$id")->Data;
        $discussion = $this->discussionModel->getID($id, DATASET_TYPE_ARRAY);
        $this->assertEquals(1, $discussion["Closed"]);
    }

    /**
     * Test `POST /discussion/delete/:id`
     */
    public function testDelete(): void
    {
        $id = $this->discussion["DiscussionID"];
        $this->bessy()->post("/discussion/delete/$id");
        $row = $this->discussionModel->getID($id);
        $this->assertFalse($row);
    }

    /**
     * Smoke test a basic discussion rendering.
     *
     * @return array
     */
    public function testDiscussionsHtml(): array
    {
        $discussion = $this->createDiscussion([
            "name" => "Hello Discussion",
            "body" => "Hello discussion body",
            "format" => MarkdownFormat::FORMAT_KEY,
        ]);
        $this->createComment(["body" => "Hello Comment", "format" => MarkdownFormat::FORMAT_KEY]);
        $doc = $this->bessy()->getHtml(
            "/discussion/{$discussion["discussionID"]}",
            [],
            ["deliveryType" => DELIVERY_TYPE_ALL]
        );

        $doc->assertCssSelectorText("h1", "Hello Discussion");
        $doc->assertCssSelectorText(".ItemDiscussion .userContent", "Hello discussion body");
        $doc->assertCssSelectorText(".ItemComment .userContent", "Hello Comment");

        return $discussion;
    }

    /**
     * Smoke test `/discussion/embed/:id`.
     */
    public function testEmbedHtml(): void
    {
        $row = $this->testDiscussionsHtml();

        $doc = $this->bessy()->getHtml(
            "/discussion/embed/{$row["discussionID"]}",
            [],
            ["deliveryType" => DELIVERY_TYPE_ALL]
        );

        $doc->assertCssSelectorText("title", "Hello Discussion — DiscussionsControllerTest");
        $doc->assertCssSelectorText(".ItemComment .userContent", "Hello Comment");
    }

    /**
     * There is a bug where marking a category read then never allows discussions to be marked read.
     *
     * @see https://higherlogic.atlassian.net/browse/VNLA-652
     */
    public function testCategoryMarkReadBug(): void
    {
        // Move the time back in case there is other code still not using `CurrentTimeStamp`.
        CurrentTimeStamp::mockTime(CurrentTimeStamp::get() - 1);

        $category = $this->createCategory(["name" => "foop"]);
        $discussion = $this->createDiscussion(["name" => "foop", "categoryID" => $category["categoryID"]]);
        $comment = $this->createComment();

        self::$categoryModel->saveUserTree($category["categoryID"], ["DateMarkedRead" => CurrentTimeStamp::getMySQL()]);

        // Move the time forward so that new comments won't conflict with the category date marked read.
        CurrentTimeStamp::mockTime(CurrentTimeStamp::get() + 1);

        // Delete the user discussion entry to simulate the bug.
        $this->discussionModel->SQL->delete("UserDiscussion", [
            "UserID" => $this->getSession()->UserID,
            "DiscussionID" => $discussion["discussionID"],
        ]);
        $userID = $this->getSession()->UserID;

        // There is currently a bug where user data is not joined when loading just one category at a time.
        // We need to peg the category model in the container because it is not normally shared during tests, but is in production.
        $categoryModel = $this->container()->get(CategoryModel::class);
        $this->container()->setInstance(CategoryModel::class, $categoryModel);
        $categoryModel->setJoinUserCategory(true);

        // Now let's read the discussion with bessy and see if there is a latest marker.
        $controller = $this->bessy()->get("/discussion/{$discussion["discussionID"]}/xxx");

        // The discussion should be marked read at this point. Let's look for an entry in user discussion.
        $discussionDb = $this->discussionModel->getID($discussion["discussionID"]);
        $this->assertSame(1, (int) $discussionDb->CountCommentWatch);
    }
}
