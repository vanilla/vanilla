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
use VanillaTests\SiteTestCase;
use VanillaTests\SiteTestTrait;
use VanillaTests\VanillaTestCase;

/**
 * Class DiscussionsControllerTest
 */
class DiscussionsControllerTest extends SiteTestCase
{
    use CommunityApiTestTrait, TestDiscussionModelTrait;

    /** @var CategoryModel */
    private static $categoryModel;

    /**
     * @var array
     */
    private $discussion;

    /**
     * {@inheritdoc}
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->useLegacyLayouts();
        self::$categoryModel = self::container()->get(CategoryModel::class);
        $this->discussion = $this->insertDiscussions(1)[0];
    }

    /**
     * {@inheritdoc}
     */
    public function tearDown(): void
    {
        parent::tearDown();
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
     * Test that the recent discussion can also be filtered by parameter followed=true
     *
     * @depends testFollowedRecentDiscussions
     * @return void
     */
    public function testFollowedRecentDiscussionsWithFollowedParameterTrue($data): void
    {
        $id = $this->discussion["DiscussionID"];
        $this->bessy()->post("/discussion/announce/$id", ["Announce" => 1]);
        $newData = $this->bessy()->get("/discussions?followed=true")->Data;
        $this->assertEquals($data["Discussions"], $newData["Discussions"]);
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
        $this->api()->post("tick", [
            "discussionID" => $discussion["discussionID"],
            "type" => "discussion_view",
        ]);
        // The discussion should be marked read at this point. Let's look for an entry in user discussion.
        $discussionDb = $this->discussionModel->getID($discussion["discussionID"]);
        $this->assertSame(1, (int) $discussionDb->CountCommentWatch);
    }

    /**
     **
     * Test if we are rendering all the discussions and discussions from followed categories, and the order is correct.
     *
     */
    public function testAnnouncementPinning(): void
    {
        $this->runWithConfig(
            [
                \CategoryModel::CONF_CATEGORY_FOLLOWING => true,
            ],
            function () {
                $this->resetTable("Discussion");

                // Let's create categories and follow them
                $category1 = $this->api()->post("categories", [
                    "name" => "testCat1",
                    "urlcode" => "test-cat-1",
                ]);
                $testCategory1ID = $category1["categoryID"];

                $category2 = $this->api()->post("categories", [
                    "name" => "testCat2",
                    "urlcode" => "test-cat-2",
                ]);
                $testCategory2ID = $category2["categoryID"];

                $this->api()->put("categories/{$testCategory1ID}/follow", ["followed" => true]);
                $this->api()->put("categories/{$testCategory2ID}/follow", ["followed" => true]);

                // This one is older and should be last, even though it is pinned in the category.
                CurrentTimeStamp::mockTime("2020-01-01");
                // pinned in category
                $this->createDiscussion([
                    "name" => "Pinned in category",
                    "pinned" => true,
                    "categoryID" => $testCategory2ID,
                    "pinLocation" => "category",
                ]);

                // These ones are newer
                CurrentTimeStamp::mockTime("2020-01-02");
                // not pinned
                $this->createDiscussion([
                    "name" => "Not pinned",
                    "categoryID" => $testCategory1ID,
                ]);
                // pinned globally
                $this->createDiscussion([
                    "name" => "Pinned globally",
                    "pinned" => true,
                    "categoryID" => $testCategory2ID,
                    "pinLocation" => "recent",
                ]);

                $assertControllerData = function (\Gdn_Controller $controller) {
                    //we have only global announcements first, then we have the rest, by most recent order
                    $this->assertRowsLike(
                        [
                            "Name" => ["Pinned globally"],
                        ],
                        $controller->data("Announcements")->resultArray(),
                        true,
                        1
                    );

                    $this->assertRowsLike(
                        [
                            "Name" => ["Not pinned", "Pinned in category"],
                        ],
                        $controller->data("Discussions")->resultArray(),
                        true,
                        2
                    );
                };

                // Works for followed.
                $controller = $this->bessy()->get("/discussions?followed=1");
                $assertControllerData($controller);

                //same logic for all discussions
                $controller = $this->bessy()->get("/discussions");
                $assertControllerData($controller);
            }
        );
    }

    /**
     * Test that the legacy discussion controller only redirects if the discussion is a redirect. The bug was that an empty string type would trigger it.
     *
     * https://higherlogic.atlassian.net/browse/VANS-2460
     *
     * @return void
     */
    public function testRenderDiscussionNotARedirect()
    {
        $discussion = $this->createDiscussion([
            "name" => "Hello Discussion",
            "body" => "[Hello discussion body](https://google.com)",
            "format" => MarkdownFormat::FORMAT_KEY,
        ]);
        \Gdn::database()
            ->createSql()
            ->update("Discussion")
            ->where("DiscussionID", $discussion["discussionID"])
            ->set("Type", "")
            ->put();

        $url = $discussion["url"];
        self::disableFeature("customLayout.post");
        $this->bessy()
            ->getHtml($url, [
                "deliveryType" => DELIVERY_TYPE_ALL,
            ])
            ->assertCssSelectorTextContains("h1", $discussion["name"]);

        // Now if I make it a redirect it should redirect.
        \Gdn::database()
            ->createSql()
            ->update("Discussion")
            ->where("DiscussionID", $discussion["discussionID"])
            ->set("Type", "Redirect")
            ->put();

        $this->assertRedirectsTo(
            safeURL(
                "https://vanilla.test/discussionscontrollertest/home/leaving?allowTrusted=1&target=https%3A%2F%2Fgoogle.com",
                true
            ),
            301,
            function () use ($url) {
                $this->bessy()->getHtml($url, [
                    "deliveryType" => DELIVERY_TYPE_ALL,
                ]);
            }
        );
    }
}
