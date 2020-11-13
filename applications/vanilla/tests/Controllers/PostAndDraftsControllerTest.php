<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Tests\Controllers;

use PHPUnit\Framework\TestCase;
use VanillaTests\Models\TestCommentModelTrait;
use VanillaTests\Models\TestDiscussionModelTrait;
use VanillaTests\SetupTraitsTrait;
use VanillaTests\SiteTestTrait;

/**
 * Tests for the `DraftsController` class.
 */
class PostAndDraftsControllerTest extends TestCase {
    use SiteTestTrait, SetupTraitsTrait, TestDiscussionModelTrait, TestCommentModelTrait;

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
     * Instantiate fixtures.
     */
    public function setUp(): void {
        parent::setUp();
        $this->setupTestTraits();
        $this->createUserFixtures();
        $this->container()->call(function (\DraftModel $draftModel, \Gdn_Configuration $config) {
            $this->draftModel = $draftModel;
            $this->config = $config;
        });
        // This is a bit of a kluge because our test harness does not load addon config defaults.
        $this->config->set('Vanilla.Categories.Use', true);

        \Gdn::session()->start($this->memberID);
        $this->discussion = $this->insertDiscussions(1)[0];
        $this->comment = $this->insertComments(1, ['DiscussionID' => $this->discussion['DiscussionID']])[0];

        $this->discussionDraft = $this->postDiscussionDraft();

        // Save a sample draft comment for the discussion.
        $this->commentDraft = $this->postCommentDraft();
        debug(true);
    }

    /**
     * Save a test discussion draft.
     *
     * @return array
     */
    private function postDiscussionDraft(): array {
        $r = $this->bessy()->post(
            "/post/discussion",
            $this->discussion(['Save_Draft' => 'Save Le Draft']),
            ['deliveryMethod' => DELIVERY_METHOD_JSON]
        )->getJson();

        return $this->draftModel->getID($r['DraftID'], DATASET_TYPE_ARRAY);
    }

    /**
     * Create a test discussion.
     *
     * @param array $overrides
     * @return array
     */
    private function discussion(array $overrides = []) {
        return array_replace([
            'Name' => 'Test Discussion',
            'Body' => 'Test Discussion Body',
            'Format' => 'markdown',
            'CategoryID' => $this->discussion['CategoryID'],
            'Announce' => 0,
        ], $overrides);
    }

    /**
     * Create a test comment draft.
     *
     * @return array
     */
    private function postCommentDraft(): array {
        $r = $this->bessy()->post(
            "/post/comment?discussionID={$this->discussion['DiscussionID']}",
            $this->comment(['Type' => 'Draft']),
            ['deliveryMethod' => DELIVERY_METHOD_JSON]
        )->getJson();

        return $this->draftModel->getID($r['DraftID'], DATASET_TYPE_ARRAY);
    }

    /**
     * Return a test comment array.
     *
     * @param array $overrides
     * @return array
     */
    private function comment(array $overrides = []): array {
        return array_replace([
            'Body' => 'Test Comment Body',
            'Format' => "markdown",
            'DiscussionID' => $this->discussion['DiscussionID'],
        ], $overrides);
    }

    /**
     * {@inheritDoc}
     */
    public function tearDown(): void {
        parent::tearDown();
        $this->tearDownTestTraits();
    }

    /**
     * Test the fixtures added in `setUp()`.
     */
    public function testSetUpFixtures(): void {
        $id = (int)$this->discussionDraft['DraftID'];
        $draft = $this->draftModel->getID($id, DATASET_TYPE_ARRAY);
        $this->assertEquals($this->discussion['CategoryID'], $draft['CategoryID']);

        $id = (int)$this->commentDraft['DraftID'];
        $draft = $this->draftModel->getID($id, DATASET_TYPE_ARRAY);
        $this->assertEquals($this->discussion['DiscussionID'], $draft['DiscussionID']);
    }

    /**
     * Try posting a comment as pure data, simulating an APIv0 request.
     */
    public function testPostCommentJson(): void {
        $comment = $this->comment(['Body' => __FUNCTION__]);

        $r = $this->bessy()->postJsonData(
            "/post/comment.json",
            $comment
        );

        $this->assertSame($comment['Body'], $r['Comment']['Body']);
        $this->assertSame($comment['Format'], $r['Comment']['Format']);
    }

    /**
     * An embedded comment creates the discussion.
     */
    public function testPostEmbeddedComment(): void {
        $this->runWithConfig(['Garden.Embed.Allow' => true], function () {
            $proxy = $this->createMock(\ProxyRequest::class);
            $proxy->method('request')
                ->willReturn(<<<HTML
<html>
<title>Foo</title>
<meta name="description" value="Test" />
<body>
    <h1>Foo</h1>
</body>
</html>
HTML
            );
            $proxy->method('status')
                ->willReturn(200);

            try {
                $this->container()->setInstance(\ProxyRequest::class, $proxy);
                $r = $this->bessy()->post(
                    "/post/comment",
                    [
                        'Body' => 'foo',
                        'Format' => 'markdown',
                        'vanilla_identifier' => __FUNCTION__,
                        'vanilla_url' => 'https://example.com',
                    ]
                );
                $json = $r->getJson();
                $discussion = $this->discussionModel->getID($json['DiscussionID'], DATASET_TYPE_ARRAY);
                $comment = $this->commentModel->getID($json['CommentID'], DATASET_TYPE_ARRAY);

                $this->assertNotFalse($discussion);
                $this->assertNotFalse($comment);

                $this->assertSame('foo', $comment['Body']);
                $this->assertSame('Foo', $discussion['Name']);
                $this->assertStringContainsString('https://example.com', $discussion['Body']);
            } finally {
                $this->container()->setInstance(\ProxyRequest::class, null);
            }
        });
    }

    /**
     * Editing a draft should fill the form with its data.
     */
    public function testGetDiscussionDraft(): void {
        /** @var \PostController $r */
        $r = $this->bessy()->get(
            '/post/edit-discussion',
            ['draftID' => $this->discussionDraft['DraftID']]
        );

        $this->assertEquals($this->discussionDraft['CategoryID'], $r->Form->getValue('CategoryID'));
        $this->assertSame($this->discussionDraft['Name'], $r->Form->getValue('Name'));
        $this->assertSame($this->discussionDraft['Body'], $r->Form->getValue('Body'));
    }

    /**
     * Editing a discussion should fill its form values.
     */
    public function testGetDiscussionEdit(): void {
        \Gdn::session()->start($this->moderatorID);

        /** @var \PostController $r */
        $r = $this->bessy()->get(
            '/post/edit-discussion',
            ['discussionID' => $this->discussion['DiscussionID']]
        );

        $this->assertEquals($this->discussion['CategoryID'], $r->Form->getValue('CategoryID'));
        $this->assertSame($this->discussion['Name'], $r->Form->getValue('Name'));
        $this->assertSame($this->discussion['Body'], $r->Form->getValue('Body'));
    }

    /**
     * Test saving over top of an existing discussion draft.
     */
    public function testSaveExistingDiscussionDraft(): void {
        $updated = $this->bessy()->post(
            "/post/discussion",
            $this->discussion([
                'DraftID' => $this->discussionDraft['DraftID'],
                'Body' => __FUNCTION__,
                'Save_Draft' => 'Save Le Draft',
            ]),
            ['deliveryMethod' => DELIVERY_METHOD_JSON]
        )->getJson();
        $draft = $this->draftModel->getID($this->discussionDraft['DraftID'], DATASET_TYPE_ARRAY);

        $this->assertSame('Test Discussion', $draft['Name']);
        $this->assertSame(__FUNCTION__, $draft['Body']);
    }

    /**
     * Posting a draft should delete it and post.
     */
    public function testPostDiscussionDraft(): void {
        $updated = $this->bessy()->post(
            "/post/discussion",
            $this->discussion([
                'Body' => __FUNCTION__,
                'DraftID' => $this->discussionDraft['DraftID'],
            ]),
            ['deliveryMethod' => DELIVERY_METHOD_JSON]
        )->getJson();
        $draft = $this->draftModel->getID($this->discussionDraft['DraftID'], DATASET_TYPE_ARRAY);
        $this->assertFalse($draft);

        $discussion = $this->discussionModel->getID($updated['DiscussionID'], DATASET_TYPE_ARRAY);
        $this->assertSame(__FUNCTION__, $discussion['Body']);
    }

    /**
     * Editing a draft should fill the form with its data.
     */
    public function testGetCommentDraft(): void {
        /** @var \PostController $r */
        $r = $this->bessy()->get(
            '/post/edit-comment',
            ['draftID' => $this->commentDraft['DraftID']]
        );

        $this->assertEquals($this->commentDraft['DiscussionID'], $r->Form->getValue('DiscussionID'));
        $this->assertSame($this->commentDraft['Body'], $r->Form->getValue('Body'));
    }

    /**
     * Editing a discussion should fill its form values.
     */
    public function testGetCommentEdit(): void {
        \Gdn::session()->start($this->moderatorID);

        /** @var \PostController $r */
        $r = $this->bessy()->get(
            '/post/edit-comment',
            ['commentID' => $this->comment['CommentID']]
        );

        $this->assertEquals($this->comment['DiscussionID'], $r->Form->getValue('DiscussionID'));
        $this->assertSame($this->comment['Body'], $r->Form->getValue('Body'));
    }

    /**
     * Test saving over top of an existing draft.
     */
    public function testSaveExistingCommentDraft(): void {
        $updated = $this->bessy()->post(
            "/post/comment?discussionID={$this->discussion['DiscussionID']}",
            $this->comment([
                'Body' => __FUNCTION__,
                'DraftID' => $this->commentDraft['DraftID'],
                'Type' => 'Draft',
            ]),
            ['deliveryMethod' => DELIVERY_METHOD_JSON]
        )->getJson();
        $draft = $this->draftModel->getID($this->commentDraft['DraftID'], DATASET_TYPE_ARRAY);

        $this->assertSame(__FUNCTION__, $draft['Body']);
    }

    /**
     * Posting a draft should delete it and post.
     */
    public function testPostCommentDraft(): void {
        $updated = $this->bessy()->post(
            "/post/comment?discussionID={$this->discussion['DiscussionID']}",
            $this->comment([
                'Body' => __FUNCTION__,
                'DraftID' => $this->commentDraft['DraftID'],
            ]),
            ['deliveryMethod' => DELIVERY_METHOD_JSON]
        )->getJson();
        $draft = $this->draftModel->getID($this->commentDraft['DraftID'], DATASET_TYPE_ARRAY);
        $this->assertFalse($draft);

        $comment = $this->commentModel->getID($updated['CommentID'], DATASET_TYPE_ARRAY);
        $this->assertSame(__FUNCTION__, $comment['Body']);
    }

    /**
     * Smoke test `/drafts`.
     */
    public function testDraftsIndex(): void {
        $drafts = $this->bessy()->get('/drafts/0')->DraftData->resultArray();

        $this->assertNotEmpty($drafts);
        $this->assertArrayHasRow($drafts, ['DraftID' => $this->commentDraft['DraftID']]);
    }

    /**
     * Smoke test `/drafts/delete`.
     */
    public function testDeleteDraft(): void {
        $deleted = $this->bessy()->post(
            "/drafts/delete/{$this->commentDraft['DraftID']}",
            [],
            ['deliveryMethod' => DELIVERY_METHOD_JSON]
        );

        $draft = $this->draftModel->getID($this->commentDraft['DraftID']);
        $this->assertFalse($draft);
    }

    /**
     * The new discussion form should be set from the query.
     */
    public function testPopulateDiscussionForm(): void {
        /** @var \PostController $r */
        $r = $this->bessy()->get(
            "/post/discussion",
            ['categoryID' => $this->discussion['CategoryID'], 'name' => 'a', 'body' => __FUNCTION__]
        );

        $this->assertEquals($this->discussion['CategoryID'], $r->Form->getValue('CategoryID'));
        $this->assertSame('a', $r->Form->getValue('Name'));
        $this->assertSame(__FUNCTION__, $r->Form->getValue('Body'));
    }

    /**
     * Test the discussion preview functionality.
     */
    public function testDiscussionPreview(): void {
        $r = $this->bessy()->postHtml(
            '/post/discussion',
            $this->discussion(['Preview' => 'Prev', 'Body' => '[foo](http://example.com)'])
        );

        $r->assertCssSelectorText('a', 'foo');
        $r->assertCssSelectorExists('a[href="http://example.com"]');
    }

    /**
     * Test the discussion preview functionality.
     */
    public function testCommentPreview(): void {
        $r = $this->bessy()->postHtml(
            '/post/comment',
            $this->comment(['Type' => 'Preview', 'Body' => '[foo](http://example.com)'])
        );

        $r->assertCssSelectorText('a', 'foo');
        $r->assertCssSelectorExists('a[href="http://example.com"]');
    }

    /**
     * The new discussion form should be set from the query.
     */
    public function testPopulateDiscussionForm2(): void {
        $cat = \CategoryModel::categories($this->discussion['CategoryID']);

        /** @var \PostController $r */
        $r = $this->bessy()->get(
            "/post/discussion",
            ['category' => $cat['UrlCode']]
        );

        $this->assertEquals($cat['CategoryID'], $r->Form->getValue('CategoryID'));
    }
}
