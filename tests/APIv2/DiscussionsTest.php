<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

use CategoryModel;
use DiscussionModel;
use Garden\Web\Exception\ForbiddenException;

/**
 * Test the /api/v2/discussions endpoints.
 */
class DiscussionsTest extends AbstractResourceTest {
    use TestPutFieldTrait;

    /** @var array */
    private static $categoryIDs = [];

    /**
     * {@inheritdoc}
     */
    public function __construct($name = null, array $data = [], $dataName = '') {
        $this->baseUrl = '/discussions';

        $this->patchFields = ['body', 'categoryID', 'closed', 'format', 'name', 'pinLocation', 'pinned', 'sink'];

        parent::__construct($name, $data, $dataName);
    }

    /**
     * {@inheritdoc}
     */
    public function record() {
        $record = $this->record;
        $record += ['categoryID' => reset(self::$categoryIDs), 'name' => __CLASS__];
        return $record;
    }

    /**
     * {@inheritdoc}
     */
    protected function modifyRow(array $row) {
        $row = parent::modifyRow($row);

        if (array_key_exists('categoryID', $row) && !in_array($row['categoryID'], self::$categoryIDs)) {
            throw new \Exception('Provided category ID ('.$row['categoryID'].') was not associated with a valid test category');
        }

        $row['closed'] = !$row['closed'];
        $row['pinned'] = !$row['pinned'];
        if ($row['pinned']) {
            $row['pinLocation'] = $row['pinLocation'] == 'category' ? 'recent' : 'category';
        } else {
            $row['pinLocation'] = null;
        }
        $row['sink'] = !$row['sink'];

        return $row;
    }

    /**
     * {@inheritdoc}
     */
    public function providePutFields() {
        $fields = [
            'bookmark' => ['bookmark', true, 'bookmarked'],
        ];
        return $fields;
    }

    /**
     * {@inheritdoc}
     */
    public static function setupBeforeClass(): void {
        parent::setupBeforeClass();

        /** @var CategoryModel $categoryModel */
        $categoryModel = self::container()->get('CategoryModel');
        $categories = ['Test Category A', 'Test Category B', 'Test Category C'];
        foreach ($categories as $category) {
            $urlCode = preg_replace('/[^A-Z0-9]+/i', '-', strtolower($category));
            self::$categoryIDs[] = $categoryModel->save([
                'Name' => $category,
                'UrlCode' => $urlCode,
                'InsertUserID' => self::$siteInfo['adminUserID']
            ]);
        }
    }

    /**
     * @inheritDoc
     */
    public function setUp(): void {
        parent::setUp();
        DiscussionModel::categoryPermissions(false, true);
    }
    /**
     * Verify a bookmarked discussion shows up under /discussions/bookmarked.
     */
    public function testBookmarked() {
        $row = $this->testPost();
        $rowID = $row['discussionID'];
        $this->api()->put("{$this->baseUrl}/{$row[$this->pk]}/bookmark", ['bookmarked' => 1]);
        $bookmarked = $this->api()->get("{$this->baseUrl}/bookmarked")->getBody();
        $discussionIDs = array_column($bookmarked, 'discussionID');
        $this->assertContains($rowID, $discussionIDs);
    }

    /**
     * Test getting a list of discussions from followed categories.
     */
    public function testIndexFollowed() {
        // Make sure we're starting from scratch.
        $preFollow = $this->api()->get($this->baseUrl, ['followed' => true])->getBody();
        $this->assertEmpty($preFollow);

        // Create a new category to follow.
        $category = $this->api()->post("categories", [
            'name' => __FUNCTION__,
            'urlcode' => __FUNCTION__
        ]);
        $testCategoryID = $category['categoryID'];
        $this->api()->put("categories/{$testCategoryID}/follow", ['followed' => true]);

        // Add some discussions
        $totalDiscussions = 3;
        $record = $this->record();
        $record['categoryID'] = $testCategoryID;
        for ($i = 1; $i <= $totalDiscussions; $i++) {
            $this->testPost($record);
        }

        // See if we have any discussions.
        $postFollow = $this->api()->get($this->baseUrl, ['followed' => true])->getBody();
        $this->assertCount($totalDiscussions, $postFollow);

        // Make sure discussions are only from the followed category.
        $categoryIDs = array_unique(array_column($postFollow, 'categoryID'));
        $this->assertCount(1, $categoryIDs);
        $this->assertEquals($testCategoryID, $categoryIDs[0]);
    }

    /**
     * Test PATCH /discussions/<id> with a a single field update.
     *
     * @param string $field The name of the field to patch.
     * @dataProvider providePatchFields
     */
    public function testPatchSparse($field) {
        // pinLocation doesn't do anything on its own, it requires pinned. It's not a good candidate for a single-field sparse PATCH.
        if ($field == 'pinLocation') {
            $this->assertTrue(true);
            return;
        }

        parent::testPatchSparse($field);
    }

    /**
     * Test PUT /discussions/{id}/canonical-url when not set
     */
    public function testPutCanonicalUrl() {
        $row = $this->testPost();
        $url = '/canonical/url/test';
        $discussion = $this->api()->put($this->baseUrl.'/'.$row['discussionID'].'/canonical-url', ['canonicalUrl' => $url])->getBody();
        $this->assertArrayHasKey('canonicalUrl', $discussion);
        $this->assertEquals($url, $discussion['canonicalUrl']);
    }

    /**
     * Test PUT /discussions/{id}/canonical-url when already set up
     */
    public function testOverwriteCanonicalUrl() {
        $row = $this->testPost();
        $url = '/canonical/url/test';
        $discussion = $this->api()->put($this->baseUrl.'/'.$row['discussionID'].'/canonical-url', ['canonicalUrl' => $url])->getBody();
        $this->assertArrayHasKey('canonicalUrl', $discussion);
        $this->assertEquals($url, $discussion['canonicalUrl']);

        $this->expectException(\Garden\Web\Exception\ClientException::class);
        $this->api()->put($this->baseUrl.'/'.$row['discussionID'].'/canonical-url', ['canonicalUrl' => $url.'overwrite']);
    }

    /**
     * Test DELETE /discussions/{id}/canonical-url
     */
    public function testDeleteCanonicalUrl() {
        $row = $this->testPost();
        $url = '/canonical/url/test';
        $discussion = $this->api()->put($this->baseUrl.'/'.$row['discussionID'].'/canonical-url', ['canonicalUrl' => $url])->getBody();
        $response = $this->api()->delete($this->baseUrl.'/'.$row['discussionID'].'/canonical-url');

        $this->assertEquals('204 No Content', $response->getStatus());

        $discussion = $response->getBody();
        $this->assertTrue(empty($discussion));

        $discussion = $this->api()->get($this->baseUrl.'/'.$row['discussionID'])->getBody();
        $this->assertNotEquals($url, $discussion['canonicalUrl']);
        $this->assertEquals($discussion['url'], $discussion['canonicalUrl']);
    }

    /**
     * The discussion index should fail on a private community with a guest.
     */
    public function testIndexPrivateCommunity() {
        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage('You must sign in to the private community.');

        $this->runWithPrivateCommunity([$this, 'testIndex']);
    }

    /**
     * Test the new dateLastComment filter.
     */
    public function testDateLastCommentFilter() {
        $this->generateIndexRows();
        sleep(1);
        $rows = $this->generateIndexRows();
        $row0 = $rows[0];
        $this->assertNotEmpty($row0['dateLastComment']);

        $filteredRows = $this->api()->get('/discussions', ['dateLastComment' => '<'.$row0['dateLastComment']])->getBody();
        $filteredRow0 = $filteredRows[0];
        $this->assertNotSame($row0['discussionID'], $filteredRow0['discussionID']);
    }

    /**
     * Test comment body expansion.
     */
    public function testExpandLastPostBody() {
        $this->testPost();

        // Test that the field is there.
        $query = ['expand' => 'lastPost,lastPost.body'];
        $rows = $this->api()->get($this->baseUrl, $query);
        $this->assertArrayHasKey('body', $rows[0]['lastPost']);

        // Comment on a discussions to see if it becomes the last post.
        $comment = $this->api()->post("/comments", [
            'discussionID' => $rows[0]['discussionID'],
            'body' => 'hello',
            'format' => 'markdown',
        ]);

        $rows = $this->api()->get($this->baseUrl, $query);
        $this->assertSame($comment['commentID'], $rows[0]['lastPost']['commentID']);

        // Individual discussions should expand too.
        $discussion = $this->api()->get($this->baseUrl.'/'.$rows[0]['discussionID'], $query);
        $this->assertArrayHasKey('body', $discussion['lastPost']);
        $this->assertSame($comment['commentID'], $discussion['lastPost']['commentID']);
    }

    /**
     * @requires testExpandLastPostBody
     */
    public function testExpandLastUser() {
        $rows = $this->api()->get($this->baseUrl, ['expand' => 'lastPost,lastPost.insertUser']);
        $this->assertArrayHasKey('insertUser', $rows[0]['lastPost']);
        $this->assertArrayNotHasKey('lastUser', $rows[0]);

        // Deprecated but should work for BC.
        $rows = $this->api()->get($this->baseUrl, ['expand' => 'lastPost,lastUser']);
        $this->assertArrayHasKey('insertUser', $rows[0]['lastPost']);
        $this->assertArrayHasKey('lastUser', $rows[0]);

        $url = $this->baseUrl.'/'.$rows[0]['discussionID'];
        $row = $this->api()->get($url, ['expand' => 'lastPost,lastPost.insertUser']);
        $this->assertArrayHasKey('insertUser', $row['lastPost']);
        $this->assertArrayNotHasKey('lastUser', $row);
    }

    /**
     * The API should not fail when the discussion title/body is empty.
     */
    public function testEmptyDiscussionTitle() {
        $row = $this->testPost();

        /* @var \Gdn_SQLDriver $sql */
        $sql = self::container()->get(\Gdn_SQLDriver::class);
        $sql->put('Discussion', ['Name' => '', 'Body' => ''], ['DiscussionID' => $row['discussionID']]);

        $discussion = $this->api()->get("$this->baseUrl/{$row['discussionID']}")->getBody();
        $this->assertNotEmpty($discussion['name']);
    }
}
