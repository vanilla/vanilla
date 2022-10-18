<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Tests\Controllers;

use Garden\Schema\ValidationException;
use PHPUnit\Framework\TestCase;
use Vanilla\CurrentTimeStamp;
use Vanilla\EmbeddedContent\Embeds\FileEmbed;
use Vanilla\EmbeddedContent\Embeds\ImageEmbed;
use Vanilla\EmbeddedContent\Embeds\QuoteEmbed;
use Vanilla\EmbeddedContent\EmbedService;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\Models\TestCategoryModelTrait;
use VanillaTests\Models\TestCommentModelTrait;
use VanillaTests\Models\TestDiscussionModelTrait;
use VanillaTests\SetupTraitsTrait;
use VanillaTests\SiteTestTrait;

/**
 * Tests for the `DraftsController` class.
 */
class PostAndDraftsControllerTest extends TestCase
{
    use SiteTestTrait,
        SetupTraitsTrait,
        TestDiscussionModelTrait,
        TestCommentModelTrait,
        TestCategoryModelTrait,
        CommunityApiTestTrait;

    /**
     * @var array
     */
    private $categoryWithoutAllowFileUploads;

    /**
     * @var array
     */
    private $discussion;

    /**
     * @var array
     */
    private $commentDraft;

    /**
     * @var \DraftModel
     */
    private $draftModel;

    /**
     * @var array
     */
    private $discussionDraft;

    /**
     * @var array
     */
    private $comment;

    /**
     * @var \Gdn_Configuration
     */
    private $config;

    /**
     * @inheritDoc
     */
    public static function setupBeforeClass(): void
    {
        self::$addons = ["vanilla", "editor"];
        self::setupBeforeClassSiteTestTrait();
    }

    /**
     * Instantiate fixtures.
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->setupTestTraits();
        $this->createUserFixtures();
        $this->container()->call(function (\DraftModel $draftModel, \Gdn_Configuration $config) {
            $this->draftModel = $draftModel;
            $this->config = $config;
        });
        // This is a bit of a kluge because our test harness does not load addon config defaults.
        $this->config->set("Vanilla.Categories.Use", true);

        \Gdn::session()->start($this->memberID);
        $this->discussion = $this->insertDiscussions(1)[0];
        $this->comment = $this->insertComments(1, ["DiscussionID" => $this->discussion["DiscussionID"]])[0];

        $this->discussionDraft = $this->postDiscussionDraft();

        // Save a sample draft comment for the discussion.
        $this->commentDraft = $this->postCommentDraft();
    }

    /**
     * Save a test discussion draft.
     *
     * @return array
     */
    private function postDiscussionDraft(): array
    {
        $r = $this->bessy()
            ->post("/post/discussion", $this->discussion(["Save_Draft" => "Save Le Draft"]), [
                "deliveryMethod" => DELIVERY_METHOD_JSON,
            ])
            ->getJson();

        return $this->draftModel->getID($r["DraftID"], DATASET_TYPE_ARRAY);
    }

    /**
     * Create a test discussion.
     *
     * @param array $overrides
     * @return array
     */
    private function discussion(array $overrides = [])
    {
        return array_replace(
            [
                "Name" => "Test Discussion",
                "Body" => "Test Discussion Body",
                "Format" => "markdown",
                "CategoryID" => $this->discussion["CategoryID"],
                "Announce" => 0,
            ],
            $overrides
        );
    }

    /**
     * Create a test comment draft.
     *
     * @return array
     */
    private function postCommentDraft(): array
    {
        $r = $this->bessy()
            ->post(
                "/post/comment?discussionID={$this->discussion["DiscussionID"]}",
                $this->comment(["Type" => "Draft"]),
                ["deliveryMethod" => DELIVERY_METHOD_JSON]
            )
            ->getJson();

        return $this->draftModel->getID($r["DraftID"], DATASET_TYPE_ARRAY);
    }

    /**
     * Return a test comment array.
     *
     * @param array $overrides
     * @return array
     */
    private function comment(array $overrides = []): array
    {
        return array_replace(
            [
                "Body" => "Test Comment Body",
                "Format" => "markdown",
                "DiscussionID" => $this->discussion["DiscussionID"],
            ],
            $overrides
        );
    }

    /**
     * {@inheritDoc}
     */
    public function tearDown(): void
    {
        parent::tearDown();
        $this->tearDownTestTraits();
    }

    /**
     * Test the fixtures added in `setUp()`.
     */
    public function testSetUpFixtures(): void
    {
        $id = (int) $this->discussionDraft["DraftID"];
        $draft = $this->draftModel->getID($id, DATASET_TYPE_ARRAY);
        $this->assertEquals($this->discussion["CategoryID"], $draft["CategoryID"]);

        $id = (int) $this->commentDraft["DraftID"];
        $draft = $this->draftModel->getID($id, DATASET_TYPE_ARRAY);
        $this->assertEquals($this->discussion["DiscussionID"], $draft["DiscussionID"]);
    }

    /**
     * Try posting a comment as pure data, simulating an APIv0 request.
     */
    public function testPostCommentJson(): void
    {
        $comment = $this->comment(["Body" => __FUNCTION__]);

        $r = $this->bessy()->postJsonData("/post/comment.json", $comment);

        $this->assertSame($comment["Body"], $r["Comment"]["Body"]);
        $this->assertSame($comment["Format"], $r["Comment"]["Format"]);
    }

    /**
     * An embedded comment creates the discussion.
     */
    public function testPostEmbeddedComment(): void
    {
        $this->runWithConfig(["Garden.Embed.Allow" => true], function () {
            $proxy = $this->createMock(\ProxyRequest::class);
            $proxy->method("request")->willReturn(
                <<<HTML
<html>
<title>Foo</title>
<meta name="description" value="Test" />
<body>
    <h1>Foo</h1>
</body>
</html>
HTML
            );
            $proxy->method("status")->willReturn(200);

            try {
                $this->container()->setInstance(\ProxyRequest::class, $proxy);
                $r = $this->bessy()->post("/post/comment", [
                    "Body" => "foo",
                    "Format" => "markdown",
                    "vanilla_identifier" => __FUNCTION__,
                    "vanilla_url" => "https://example.com",
                ]);
                $json = $r->getJson();
                $discussion = $this->discussionModel->getID($json["DiscussionID"], DATASET_TYPE_ARRAY);
                $comment = $this->commentModel->getID($json["CommentID"], DATASET_TYPE_ARRAY);

                $this->assertNotFalse($discussion);
                $this->assertNotFalse($comment);

                $this->assertSame("foo", $comment["Body"]);
                $this->assertSame("Foo", $discussion["Name"]);
                $this->assertStringContainsString("https://example.com", $discussion["Body"]);
            } finally {
                $this->container()->setInstance(\ProxyRequest::class, null);
            }
        });
    }

    /**
     * Editing a draft should fill the form with its data.
     */
    public function testGetDiscussionDraft(): void
    {
        /** @var \PostController $r */
        $r = $this->bessy()->get("/post/edit-discussion", ["draftID" => $this->discussionDraft["DraftID"]]);

        $this->assertEquals($this->discussionDraft["CategoryID"], $r->Form->getValue("CategoryID"));
        $this->assertSame($this->discussionDraft["Name"], $r->Form->getValue("Name"));
        $this->assertSame($this->discussionDraft["Body"], $r->Form->getValue("Body"));
    }

    /**
     * Editing a discussion should fill its form values.
     */
    public function testGetDiscussionEdit(): void
    {
        \Gdn::session()->start($this->moderatorID);

        /** @var \PostController $r */
        $r = $this->bessy()->get("/post/edit-discussion", ["discussionID" => $this->discussion["DiscussionID"]]);

        $this->assertEquals($this->discussion["CategoryID"], $r->Form->getValue("CategoryID"));
        $this->assertSame($this->discussion["Name"], $r->Form->getValue("Name"));
        $this->assertSame($this->discussion["Body"], $r->Form->getValue("Body"));
    }

    /**
     * Test saving over top of an existing discussion draft.
     */
    public function testSaveExistingDiscussionDraft(): void
    {
        $updated = $this->bessy()
            ->post(
                "/post/discussion",
                $this->discussion([
                    "DraftID" => $this->discussionDraft["DraftID"],
                    "Body" => __FUNCTION__,
                    "Save_Draft" => "Save Le Draft",
                ]),
                ["deliveryMethod" => DELIVERY_METHOD_JSON]
            )
            ->getJson();
        $draft = $this->draftModel->getID($this->discussionDraft["DraftID"], DATASET_TYPE_ARRAY);

        $this->assertSame("Test Discussion", $draft["Name"]);
        $this->assertSame(__FUNCTION__, $draft["Body"]);
    }

    /**
     * Posting a draft should delete it and post.
     */
    public function testPostDiscussionDraft(): void
    {
        $updated = $this->bessy()
            ->post(
                "/post/discussion",
                $this->discussion([
                    "Body" => __FUNCTION__,
                    "DraftID" => $this->discussionDraft["DraftID"],
                ]),
                ["deliveryMethod" => DELIVERY_METHOD_JSON]
            )
            ->getJson();
        $draft = $this->draftModel->getID($this->discussionDraft["DraftID"], DATASET_TYPE_ARRAY);
        $this->assertFalse($draft);

        $discussion = $this->discussionModel->getID($updated["DiscussionID"], DATASET_TYPE_ARRAY);
        $this->assertSame(__FUNCTION__, $discussion["Body"]);
    }

    /**
     * Editing a draft should fill the form with its data.
     */
    public function testGetCommentDraft(): void
    {
        /** @var \PostController $r */
        $r = $this->bessy()->get("/post/edit-comment", ["draftID" => $this->commentDraft["DraftID"]]);

        $this->assertEquals($this->commentDraft["DiscussionID"], $r->Form->getValue("DiscussionID"));
        $this->assertSame($this->commentDraft["Body"], $r->Form->getValue("Body"));
    }

    /**
     * Editing a discussion should fill its form values.
     */
    public function testGetCommentEdit(): void
    {
        \Gdn::session()->start($this->moderatorID);

        /** @var \PostController $r */
        $r = $this->bessy()->get("/post/edit-comment", ["commentID" => $this->comment["CommentID"]]);

        $this->assertEquals($this->comment["DiscussionID"], $r->Form->getValue("DiscussionID"));
        $this->assertSame($this->comment["Body"], $r->Form->getValue("Body"));
    }

    /**
     * Test saving over top of an existing draft.
     */
    public function testSaveExistingCommentDraft(): void
    {
        $updated = $this->bessy()
            ->post(
                "/post/comment?discussionID={$this->discussion["DiscussionID"]}",
                $this->comment([
                    "Body" => __FUNCTION__,
                    "DraftID" => $this->commentDraft["DraftID"],
                    "Type" => "Draft",
                ]),
                ["deliveryMethod" => DELIVERY_METHOD_JSON]
            )
            ->getJson();
        $draft = $this->draftModel->getID($this->commentDraft["DraftID"], DATASET_TYPE_ARRAY);

        $this->assertSame(__FUNCTION__, $draft["Body"]);
    }

    /**
     * Posting a draft should delete it and post.
     */
    public function testPostCommentDraft(): void
    {
        $updated = $this->bessy()
            ->post(
                "/post/comment?discussionID={$this->discussion["DiscussionID"]}",
                $this->comment([
                    "Body" => __FUNCTION__,
                    "DraftID" => $this->commentDraft["DraftID"],
                ]),
                ["deliveryMethod" => DELIVERY_METHOD_JSON]
            )
            ->getJson();
        $draft = $this->draftModel->getID($this->commentDraft["DraftID"], DATASET_TYPE_ARRAY);
        $this->assertFalse($draft);

        $comment = $this->commentModel->getID($updated["CommentID"], DATASET_TYPE_ARRAY);
        $this->assertSame(__FUNCTION__, $comment["Body"]);
    }

    /**
     * Smoke test `/drafts`.
     */
    public function testDraftsIndex(): void
    {
        $drafts = $this->bessy()
            ->get("/drafts/0")
            ->DraftData->resultArray();

        $this->assertNotEmpty($drafts);
        $this->assertArrayHasRow($drafts, ["DraftID" => $this->commentDraft["DraftID"]]);
    }

    /**
     * Smoke test `/drafts/delete`.
     */
    public function testDeleteDraft(): void
    {
        $deleted = $this->bessy()->post(
            "/drafts/delete/{$this->commentDraft["DraftID"]}",
            [],
            ["deliveryMethod" => DELIVERY_METHOD_JSON]
        );

        $draft = $this->draftModel->getID($this->commentDraft["DraftID"]);
        $this->assertFalse($draft);
    }

    /**
     * The new discussion form should be set from the query.
     */
    public function testPopulateDiscussionForm(): void
    {
        /** @var \PostController $r */
        $r = $this->bessy()->get("/post/discussion", [
            "categoryID" => $this->discussion["CategoryID"],
            "name" => "a",
            "body" => __FUNCTION__,
        ]);

        $this->assertEquals($this->discussion["CategoryID"], $r->Form->getValue("CategoryID"));
        $this->assertSame("a", $r->Form->getValue("Name"));
        $this->assertSame(__FUNCTION__, $r->Form->getValue("Body"));
    }

    /**
     * Test the discussion preview functionality.
     */
    public function testDiscussionPreview(): void
    {
        $r = $this->bessy()->postHtml(
            "/post/discussion",
            $this->discussion(["Preview" => "Prev", "Body" => "[foo](http://example.com)"])
        );

        $r->assertCssSelectorText("a", "foo");

        $expectedHref = url(
            "/home/leaving?" .
                http_build_query([
                    "allowTrusted" => 1,
                    "target" => "http://example.com",
                ])
        );
        $r->assertCssSelectorExists('a[href="' . $expectedHref . '"]');
    }

    /**
     * Test the discussion preview functionality.
     */
    public function testCommentPreview(): void
    {
        $r = $this->bessy()->postHtml(
            "/post/comment",
            $this->comment(["Type" => "Preview", "Body" => "[foo](http://example.com)"])
        );

        $r->assertCssSelectorText("a", "foo");

        $expectedHref = url(
            "/home/leaving?" .
                http_build_query([
                    "allowTrusted" => 1,
                    "target" => "http://example.com",
                ])
        );
        $r->assertCssSelectorExists('a[href="' . $expectedHref . '"]');
    }

    /**
     * The new discussion form should be set from the query.
     */
    public function testPopulateDiscussionForm2(): void
    {
        $cat = \CategoryModel::categories($this->discussion["CategoryID"]);

        /** @var \PostController $r */
        $r = $this->bessy()->get("/post/discussion", ["category" => $cat["UrlCode"]]);

        $this->assertEquals($cat["CategoryID"], $r->Form->getValue("CategoryID"));
    }

    /**
     * @dataProvider provideDiscussionWithAttachment
     */

    /**
     * Test posting into a category with AllowFileUploads set to false and CustomPermissions set to true
     *
     * @param bool $exceptionExpected
     * @param array $discussion
     * @dataProvider provideDiscussionWithAttachment
     */
    public function testPostCategoryWithoutAllowFileUploads(bool $exceptionExpected, array $discussion): void
    {
        // Add category with AllowFileUploads.
        $this->categoryWithoutAllowFileUploads = $this->insertCategories(1, [
            "name" => "Category Without AllowFileUploads",
            "AllowFileUploads" => false,
            "CustomPermissions" => true,
        ])[0];

        // Make sure the image embed is registered.
        /** @var EmbedService $embedService */
        $embedService = \Gdn::getContainer()->get(EmbedService::class);
        $embedService->registerEmbed(ImageEmbed::class, ImageEmbed::TYPE);
        $embedService->registerEmbed(FileEmbed::class, FileEmbed::TYPE);

        $body = json_encode($discussion["Body"]);
        $discussion["Body"] = $body;

        if ($exceptionExpected) {
            $this->expectException(ValidationException::class);
        }

        /** @var \PostController $r */
        $r = $this->bessy()->post(
            "/post/discussion",
            array_merge($discussion, ["CategoryID" => $this->categoryWithoutAllowFileUploads["CategoryID"]])
        );
    }

    /**
     * Provides data for testPostCategoryWithoutAllowFileUploads
     *
     * @return array[]
     */
    public function provideDiscussionWithAttachment(): array
    {
        $r = [
            "rich discussion with image attachment" => [
                false,
                [
                    "Format" => "Rich",
                    "Name" => "Discussion with attachments",
                    "Body" => [
                        [
                            "insert" => [
                                "embed-external" => [
                                    "data" => [
                                        "url" => "https://example.com/foo.png",
                                        "name" => "foo text here",
                                        "type" => "image/png",
                                        "size" => 26734,
                                        "width" => 290,
                                        "height" => 290,
                                        "displaySize" => "medium",
                                        "float" => "none",
                                        "mediaID" => 136016,
                                        "dateInserted" => "2021-04-08T18:24:13+00:00",
                                        "insertUserID" => 1,
                                        "foreignType" => "embed",
                                        "foreignID" => 1,
                                        "embedType" => "image",
                                    ],
                                    "loaderData" => [
                                        "type" => "image",
                                    ],
                                ],
                            ],
                        ],
                        [
                            "insert" => "\n",
                        ],
                    ],
                ],
            ],
            "rich discussion with pdf attachment" => [
                true,
                [
                    "Format" => "Rich",
                    "Name" => "Discussion with attachments",
                    "Body" => [
                        [
                            "insert" => [
                                "embed-external" => [
                                    "data" => [
                                        "url" => "https://example.com/foo.pdf",
                                        "name" => "foo.pdf",
                                        "type" => "application/pdf",
                                        "size" => 7945,
                                        "displaySize" => "large",
                                        "float" => "none",
                                        "mediaID" => 55,
                                        "dateInserted" => "2022-03-22T05:29:00+00:00",
                                        "insertUserID" => 2,
                                        "foreignType" => "embed",
                                        "foreignID" => 2,
                                        "embedType" => "file",
                                    ],
                                    "loaderData" => [
                                        "type" => "file",
                                        "file" => [],
                                        "progressEventEmitter" => [
                                            "listeners" => [
                                                0 => null,
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        [
                            "insert" => "\n",
                        ],
                    ],
                ],
            ],
        ];

        return $r;
    }

    /**
     * Test that a non-discussion type category is not an available option for posting a discussion.
     */
    public function testNonDiscussionCategoriesInDropdown(): void
    {
        \Gdn::themeFeatures()->forceFeatures([
            "NewCategoryDropdown" => false,
        ]);
        $this->runWithConfig(
            [
                "Vanilla.Categories.Use" => true,
            ],
            function () {
                $this->api()->setUserID(self::$siteInfo["adminUserID"]);
                // Create a non-discussion type category.
                $newCat = $this->createCategory(["displayAs" => "heading"]);
                $content = $this->bessy()->getHtml("post/discussion/{$newCat["urlcode"]}", [
                    "deliveryType" => DELIVERY_TYPE_ALL,
                ]);
                // We should have the default text, meaning no category is pre-selected.
                $content->assertContainsString("Select a category...");
                // The heading category should not be an available option.
                $content->assertCssSelectorText("option[disabled]", $newCat["name"]);
            }
        );
    }

    /**
     * Tests that a discussion is not marked as unread after a user posts a comment
     */
    public function testDiscussionNotUnreadAfterPostComment()
    {
        $this->api()->setUserID(self::$siteInfo["adminUserID"]);
        $category = $this->createCategory();
        $discussion = $this->createDiscussion();

        CurrentTimeStamp::mockTime(CurrentTimeStamp::getDateTime()->modify("+42 seconds"));
        $comment = $this->createComment();
        $this->bessy()->post("/post/comment/?discussionid={$discussion["discussionID"]}", [
            "DiscussionID" => $discussion["discussionID"],
            "Format" => "Wysiwyg",
            "Body" => "test comment",
            "Type" => "Post",
            "LastCommentID" => $comment["commentID"],
        ]);
        CurrentTimeStamp::clearMockTime();

        // Test discussion row has "Read" class and not "Unread" class
        $html = $this->bessy()->getHtml($category["url"]);
        $html->assertCssSelectorExists("#Discussion_{$discussion["discussionID"]}.Read");
        $html->assertCssSelectorNotExists("#Discussion_{$discussion["discussionID"]}.Unread");

        // Additional check with api
        $discussion = $this->api()->get("/discussions/{$discussion["discussionID"]}");
        $this->assertFalse($discussion["unread"]);
    }
}
