<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Tests\Controllers;

use CategoryModel;
use PHPUnit\Framework\TestCase;
use Vanilla\Formatting\Formats\MarkdownFormat;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\Models\TestDiscussionModelTrait;
use VanillaTests\SetupTraitsTrait;
use VanillaTests\SiteTestTrait;
use VanillaTests\VanillaTestCase;

/**
 * Class DiscussionsControllerTest
 */
class DiscussionsControllerTest extends TestCase {
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
    public function setUp(): void {
        parent::setUp();
        $this->setupTestTraits();
        self::$categoryModel = self::container()->get(CategoryModel::class);
        $this->discussion = $this->insertDiscussions(1)[0];
    }

    /**
     * {@inheritDoc}
     */
    public function tearDown(): void {
        parent::tearDown();
        $this->tearDownTestTraits();
    }

    /**
     * Smoke test the /discussions endpoint.
     *
     * @return array
     */
    public function testRecentDiscussions(): array {
        $data = $this->bessy()->get('/discussions')->Data;
        $this->assertNotEmpty($data['Discussions']);

        return $data;
    }

    /**
     * Smoke test the /discussions endpoint.
     *
     * @return array
     */
    public function testFollowedRecentDiscussions(): array {
        /** @var \Gdn_Configuration $config */
        $config = static::container()->get('Config');
        $config->set('Vanilla.EnableCategoryFollowing', true, true, false);
        // follow a category
        self::$categoryModel->follow(\Gdn::session()->UserID, 1, true);
        $data = $this->bessy()->get('/discussions?followed=1')->Data;
        $this->assertNotEmpty($data['Discussions']);

        return $data;
    }

    /**
     * Smoke test a basic discussion fetch.
     *
     * @param array $data
     * @depends testRecentDiscussions
     */
    public function testGetDiscussion(array $data): void {
        foreach ($data['Discussions'] as $discussion) {
            $url = discussionUrl($discussion, '', '/');
            $data = $this->bessy()->get($url)->Data;

            $this->assertContains('Discussion', $data);
            $this->assertContains('Comments', $data);

            break;
        }
    }

    /**
     * Test a basic announce happy path.
     */
    public function testAnnounce(): array {
        $this->assertEquals(0, $this->discussion['Announce']);
        $id = $this->discussion['DiscussionID'];
        $data = $this->bessy()->post("/discussion/announce/$id", ['Announce' => 1])->Data;
        $discussion = $this->discussionModel->getID($id, DATASET_TYPE_ARRAY);
        $this->assertEquals(1, $discussion['Announce']);

        return $discussion;
    }

    /**
     * Test `POST /discusion/bookmark/:id`.
     */
    public function testBookmark(): void {
        $id = $this->discussion['DiscussionID'];
        $data = $this->bessy()->post("/discussion/bookmark/$id", ['Bookmark' => 1])->Data;
        $this->assertTrue((bool)$data['Bookmarked']);
        $discussions = $this->bessy()->get('/discussions/bookmarked')->data('Discussions')->resultArray();
        $row = VanillaTestCase::assertDatasetHasRow($discussions, ['DiscussionID' => $id]);
        $this->assertTrue((bool)$row['Bookmarked']);
    }

    /**
     * Test `/discussion/dismiss/:id`.
     */
    public function testDismiss(): void {
        $discussion = $this->testAnnounce();
        $id = $discussion['DiscussionID'];

        $data = $this->bessy()->post(
            "/discussion/dismiss-announcement/$id"
        );

        $discussions = $this->bessy()->get("/discussions")->data('Discussions')->resultArray();
        $row = VanillaTestCase::assertDatasetHasRow($discussions, ['DiscussionID' => $id]);
        $this->assertTrue((bool)$row['Dismissed']);
    }

    /**
     * Test sink happy path.
     */
    public function testSink(): void {
        $this->assertEquals(0, $this->discussion['Sink']);
        $id = $this->discussion['DiscussionID'];
        $data = $this->bessy()->post("/discussion/sink/$id")->Data;
        $discussion = $this->discussionModel->getID($id, DATASET_TYPE_ARRAY);
        $this->assertEquals(1, $discussion['Sink']);
    }

    /**
     * Test close happy path.
     */
    public function testClose(): void {
        $this->assertEquals(0, $this->discussion['Closed']);
        $id = $this->discussion['DiscussionID'];
        $data = $this->bessy()->post("/discussion/close/$id")->Data;
        $discussion = $this->discussionModel->getID($id, DATASET_TYPE_ARRAY);
        $this->assertEquals(1, $discussion['Closed']);
    }

    /**
     * Test `POST /discussion/delete/:id`
     */
    public function testDelete(): void {
        $id = $this->discussion['DiscussionID'];
        $this->bessy()->post("/discussion/delete/$id");
        $row = $this->discussionModel->getID($id);
        $this->assertFalse($row);
    }

    /**
     * Smoke test a basic discussion rendering.
     *
     * @return array
     */
    public function testDiscussionsHtml(): array {
        $discussion = $this->createDiscussion([
            'name' => 'Hello Discussion',
            'body' => 'Hello discussion body',
            'format' => MarkdownFormat::FORMAT_KEY,
        ]);
        $this->createComment(['body' => 'Hello Comment', 'format' => MarkdownFormat::FORMAT_KEY]);
        $doc = $this->bessy()->getHtml(
            "/discussion/{$discussion['discussionID']}",
            [],
            ['deliveryType' => DELIVERY_TYPE_ALL]
        );

        $doc->assertCssSelectorText("h1", "Hello Discussion");
        $doc->assertCssSelectorText('.ItemDiscussion .userContent', "Hello discussion body");
        $doc->assertCssSelectorText(".ItemComment .userContent", "Hello Comment");

        return $discussion;
    }

    /**
     * Smoke test `/discussion/embed/:id`.
     */
    public function testEmbedHtml(): void {
        $row = $this->testDiscussionsHtml();

        $doc = $this->bessy()->getHtml(
            "/discussion/embed/{$row['discussionID']}",
            [],
            ['deliveryType' => DELIVERY_TYPE_ALL]
        );

        $doc->assertCssSelectorText("title", "Hello Discussion — DiscussionsControllerTest");
        $doc->assertCssSelectorText(".ItemComment .userContent", "Hello Comment");
    }
}
