<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

use CategoryModel;
use DiscussionModel;
use Garden\Web\Exception\ClientException;
use Garden\Web\Exception\ForbiddenException;
use Garden\Web\Exception\NotFoundException;
use Vanilla\DiscussionTypeConverter;
use Vanilla\Exception\PermissionException;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\Models\TestDiscussionModelTrait;

/**
 * Test the /api/v2/discussions endpoints.
 */
class DiscussionsTest extends AbstractResourceTest {
    use TestExpandTrait;
    use TestPutFieldTrait;
    use AssertLoggingTrait;
    use TestPrimaryKeyRangeFilterTrait;
    use TestSortingTrait;
    use TestDiscussionModelTrait;
    use TestFilterDirtyRecordsTrait;

    /** @var array */
    private static $categoryIDs = [];

    /**
     * {@inheritdoc}
     */
    public function __construct($name = null, array $data = [], $dataName = '') {
        $this->baseUrl = '/discussions';
        $this->resourceName = 'discussion';

        $this->patchFields = ['body', 'categoryID', 'closed', 'format', 'name', 'pinLocation', 'pinned', 'sink'];
        $this->sortFields = ['dateLastComment', 'dateInserted', 'discussionID'];

        parent::__construct($name, $data, $dataName);
    }

    /**
     * @inheritdoc
     */
    protected function getExpandableUserFields() {
        return [
            'insertUser',
            'lastUser',
            // 'lastPost.insertUser' requires a last post and is not always present.
        ];
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
        $this->setupTestDiscussionModel();
        $this->createUserFixtures();
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
        $rows = $this->api()->get($this->baseUrl, ['expand' => 'lastPost,lastPost.insertUser,-lastUser']);
        $this->assertArrayHasKey('insertUser', $rows[0]['lastPost']);
        $this->assertArrayNotHasKey('lastUser', $rows[0]);

        // Deprecated but should work for BC.
        $rows = $this->api()->get($this->baseUrl, ['expand' => 'lastPost,lastUser']);
        $this->assertArrayHasKey('insertUser', $rows[0]['lastPost']);
        $this->assertArrayHasKey('lastUser', $rows[0]);

        $url = $this->baseUrl.'/'.$rows[0]['discussionID'];
        $row = $this->api()->get($url, ['expand' => 'lastPost,lastPost.insertUser,-lastUser']);
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

    /**
     * Announcements should obey the sort.
     */
    public function testAnnouncementSort(): void {
        $this->insertDiscussions(3, ['Announce' => 1]);

        $fields = ['discussionID', '-discussionID'];

        foreach ($fields as $field) {
            $rows = $this->api()->get($this->baseUrl, ['pinned' => true, 'sort' => $field])->getBody();
            $this->assertNotEmpty($rows);
            $this->assertSorted($rows, $field);
        }
    }

    /**
     * A mix of announcements and discussions should sort properly.
     */
    public function testAnnouncementMixed(): void {
        $rows = $this->insertDiscussions(2, ['Announce' => 1]);
        $rows = array_merge($rows, $this->insertDiscussions(2));
        $ids = array_column($rows, 'DiscussionID');

        $fields = ['discussionID', '-discussionID'];

        foreach ($fields as $field) {
            $rows = $this->api()->get($this->baseUrl, ['discussionID' => $ids, 'pinOrder' => 'first', 'sort' => $field])->getBody();
            $this->assertNotEmpty($rows);
            $this->assertSorted($rows, '-pinned', $field);
        }
    }

    /**
     * Make sure you can pin a discussion while posting via API.
     */
    public function testPostAnnouncement(): void {
        $r = $this->api()->post($this->baseUrl, ['pinned' => true] + $this->record())->getBody();
        $this->assertTrue($r['pinned']);
        $this->assertSame('category', $r['pinLocation']);

        $r = $this->api()->post($this->baseUrl, ['pinned' => true, 'pinLocation' => 'recent'] + $this->record())->getBody();
        $this->assertTrue($r['pinned']);
        $this->assertSame('recent', $r['pinLocation']);
    }

    /**
     * Make sure specifying discussion type returns records from the db where the type is null.
     */
    public function testGettingTypeDiscussion(): void {
        $addedDiscussions = $this->insertDiscussions(4);
        foreach ($addedDiscussions as $discussion) {
            $this->assertTrue(is_null($discussion['Type']));
        }
        $retrievedDiscussions = $this->api()->get($this->baseUrl, ['type' => 'discussion'])->getBody();
        $retrievedDiscussionsIDs = array_column($retrievedDiscussions, 'discussionID');
        foreach ($addedDiscussions as $discussion) {
            $this->assertTrue(in_array($discussion['DiscussionID'], $retrievedDiscussionsIDs));
        }
    }

    /**
     * A member should not be able to delete their own discussion.
     */
    public function testNoDeleteOwnDiscussion(): void {
        $this->getSession()->start($this->memberID);
        $discussion = $this->insertDiscussions(1)[0];
        $this->assertFalse(
            $this->getSession()->getPermissions()->has('Vanilla.Discussions.Delete', $discussion['CategoryID']),
            'The member should not have permission to delete discussions.'
        );

        $this->expectException(ForbiddenException::class);
        $this->api()->delete("/discussions/{$discussion['DiscussionID']}");
    }

    /**
     * Test expanding tags.
     */
    public function testExpandTags(): void {
        self::resetTable('Discussion');
        $discussionA = $this->testPost();
        $tagA = $this->api()->post('tags', ['name' => 'testa'.__FUNCTION__, 'urlCode'=> 'testa'.__FUNCTION__])->getBody();
        $this->api()->post("discussions/{$discussionA["discussionID"]}/tags", ["urlcodes" => [$tagA['urlcode']], "tagIDs" => [$tagA['tagID']]]);
        $discussions = $this->api()->get("discussions", ['expand' => 'tags'])->getBody();
        foreach ($discussions as $discussion) {
            $tags = $discussion['tags'];
            $this->assertEquals($tagA['tagID'], $tags[0]['tagID']);
        }
        $discussion = $this->api()->get("discussions/".$discussionA['discussionID'], ['expand' => 'tags'])->getBody();
        $tags = $discussion['tags'];
        $this->assertEquals($tagA['tagID'], $tags[0]['tagID']);
    }

    /**
     * Ensure that there are dirtyRecords for a specific resource.
     */
    protected function triggerDirtyRecords() {
        $discussion = $this->insertDiscussions(2);
        $ids = array_column($discussion, 'DiscussionID');
        /** @var DiscussionModel $discussionModel */
        $discussionModel = \Gdn::getContainer()->get(DiscussionModel::class);
        foreach ($ids as $id) {
            $discussionModel->setField($id, 'Announce', 1);
        }
    }

    /**
     * Test PUT /discussions/:id/type
     */
    public function testPutDiscussionsType() {
        $discussion = $this->insertDiscussions(1)[0];
        /** @var DiscussionModel $discussionModel */
        $discussionModel = \Gdn::getContainer()->get(DiscussionModel::class);
        $id = $discussion["DiscussionID"];
        $discussionModel->setField($id, 'Type', "Question");

        $convertedDiscussion = $this->api()->put("/discussions/{$id}/type", ["type" => "discussion"])->getBody();
        $this->assertEquals("discussion", $convertedDiscussion["type"]);
    }

    /**
     * Test PUT /discussions/:id/type with invalid type.
     */
    public function testPutDiscussionsTypeInvalidType() {
        $this->expectException(ClientException::class);
        $discussion = $this->insertDiscussions(1)[0];
        $id = $discussion["DiscussionID"];

        $convertedDiscussion = $this->api()->put("/discussions/{$id}/type", ["type" => "poll"])->getBody();
        $this->assertEquals("discussion", $convertedDiscussion["type"]);
    }

    /**
     * Test PUT /discussions/:id/type with restricted type.
     */
    public function testPutDiscussionsTypeRestrictedType() {
        $this->expectException(ClientException::class);
        $discussion = $this->insertDiscussions(1)[0];
        $id = $discussion["DiscussionID"];

        $convertedDiscussion = $this->api()->put("/discussions/{$id}/type", ["type" => DiscussionTypeConverter::RESTRICTED_TYPES[0]])->getBody();
        $this->assertEquals("discussion", $convertedDiscussion["type"]);
    }

    /**
     * Test DELETE /discussions/list
     */
    public function testDeleteDiscussionsList(): void {

        $discussionData = [
            'name' => 'Test Discussion',
            'format' => 'text',
            'body' => 'Hello Discussion',
            'categoryID' => 1,
        ];
        $countBefore = count($this->api()->get('/discussions')->getBody());
        $discussion1 = $this->api()->post('/discussions', $discussionData)->getBody();
        $discussion2 = $this->api()->post('/discussions', $discussionData)->getBody();
        // Delete 2 valid discussions.
        $this->api()->deleteWithBody("/discussions/list", ['discussionIDs' => [$discussion1['discussionID'], $discussion2['discussionID']]]);
        $countAfter = count($this->api()->get('/discussions')->getBody());
        $this->assertEquals($countBefore, $countAfter);
        $discussion3 = $this->api()->post('/discussions', $discussionData)->getBody();
        $rd = rand(5000, 60000);
        // Delete an invalid discussion.
        try {
            $this->api()->deleteWithBody("/discussions/list", ["discussionIDs" => [$discussion3['discussionID'], $rd]])->getBody();
        } catch (ClientException $e) {
            $this->assertEquals($countBefore, $countAfter);
            $this->assertEquals(400, $e->getCode());
        }
        $this->api()->setUserID(\UserModel::GUEST_USER_ID);
        try {
            $this->api()->deleteWithBody("/discussions/list", ["discussionIDs" => [$discussion1['discussionID'], $discussion2['discussionID']]]);
        } catch (\Exception $e) {
            $this->assertEquals(403, $e->getCode());
        }
    }

    /**
     * Test PUT /discussions/:id/type with no record.
     */
    public function testPutDiscussionsTypeInvalidRecord() {
        $this->expectException(ClientException::class);
        $id = null;
        $convertedDiscussion = $this->api()->put("/discussions/{$id}/type", ["type" => "discussion"])->getBody();
        $this->assertEquals("discussion", $convertedDiscussion["type"]);
    }

    /**
     * Get the resource type.
     *
     * @return array
     */
    protected function getResourceInformation(): array {
        return [
            "resourceType" => "discussion",
            "primaryKey" => "discussionID"
        ];
    }
}
