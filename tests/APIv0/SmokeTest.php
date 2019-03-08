<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv0;

/**
 * Test some basic Vanilla functionality to make sure nothing is horribly broken.
 */
class SmokeTest extends BaseTest {

    /** @var  int */
    protected static $restrictedCategoryID;

    /**
     * @var array
     */
    protected static $testUser;

    /**
     * Get the ID of the restricted category.
     *
     * @return int
     */
    public function getRestrictedCategoryID() {
        return static::$restrictedCategoryID;
    }

    /**
     * Get the testUser.
     *
     * @return array Returns the testUser.
     */
    public function getTestUser() {
        return self::$testUser;
    }

    /**
     * Set the ID of the restricted category.
     *
     * @param int $categoryID
     * @return $this
     */
    public function setRestrictedCategoryID($categoryID) {
        static::$restrictedCategoryID = $categoryID;
        return $this;
    }

    /**
     * Set the testUser.
     *
     * @param array $testUser The user to set.
     * @return StandardTest Returns `$this` for fluent calls.
     * @see APIv0::queryUserKey()
     */
    public function setTestUser($testUser) {
        static::$testUser = $testUser;
        return $this;
    }

    /**
     * Test registering a user with the basic method.
     *
     * @large
     */
    public function testRegisterBasic() {
        $this->api()->saveToConfig([
            'Garden.Registration.Method' => 'Basic',
            'Garden.Registration.ConfirmEmail' => false,
            'Garden.Registration.SkipCaptcha' => true
        ]);

        $user = [
            'Name' => 'frank',
            'Email' => 'frank@example.com',
            'Password' => 'frankwantsin',
            'PasswordMatch' => 'frankwantsin',
            'Gender' => 'm',
            'TermsOfService' => true
        ];

        // Register the user.
        $r = $this->api()->post('/entry/register.json', $user);
        $body = $r->getBody();
        $this->assertSame('Basic', $body['Method']);

        // Look for the user in the database.
        $dbUser = $this->api()->queryUserKey($user['Name'], true);
        $this->assertSame($user['Email'], $dbUser['Email']);
        $this->assertSame($user['Gender'], $dbUser['Gender']);

        // Look up the user for confirmation.
        $siteUser = $this->api()->get('/profile.json', ['username' => $user['Name']]);
        $siteUser = $siteUser['Profile'];

        $this->assertEquals($user['Name'], $siteUser['Name']);

        $this->setTestUser($siteUser);
        return $siteUser;
    }

    /**
     * Test adding an admin user.
     *
     * @large
     */
    public function testAddAdminUser() {
        $system = $this->api()->querySystemUser(true);
        $this->api()->setUser($system);

        $adminUser = [
            'Name' => 'Admin',
            'Email' => 'admin@example.com',
            'Password' => 'adminsecure'
        ];

        // Get the admin roles.
        $adminRole = $this->api()->queryOne("select * from GDN_Role where Name = :name", [':name' => 'Administrator']);
        $this->assertNotEmpty($adminRole);
        $adminUser['RoleID'] = [$adminRole['RoleID']];

        $this->api()->saveToConfig([
            'Garden.Email.Disabled' => true,
        ]);
        $r = $this->api()->post(
            '/user/add.json',
            http_build_query($adminUser)
        );
        $b = $r->getBody();
        $this->assertResponseSuccess($r);

        // Query the user in the database.
        $dbUser = $this->api()->queryUserKey('Admin', true);

        // Query the admin role.
        $userRoles = $this->api()->query("select * from GDN_UserRole where UserID = :userID", [':userID' => $dbUser['UserID']]);
        $userRoleIDs = array_column($userRoles, 'RoleID');
        $this->assertEquals($adminUser['RoleID'], $userRoleIDs);

        return $dbUser;
    }

    /**
     * Test that a category with restricted permissions can be created.
     *
     * @large
     */
    public function testCreateRestrictedCategory() {
        $r = $this->api()->post('/vanilla/settings/addcategory.json', [
            'Name' => 'Moderators Only',
            'UrlCode' => 'moderators-only',
            'DisplayAs' => 'Discussions',
            'CustomPermissions' => 1,
            'Permission' => http_build_query([
                'Category/PermissionCategoryID/0/32//Vanilla.Comments.Add',
                'Category/PermissionCategoryID/0/32//Vanilla.Comments.Delete',
                'Category/PermissionCategoryID/0/32//Vanilla.Comments.Edit',
                'Category/PermissionCategoryID/0/32//Vanilla.Discussions.Add',
                'Category/PermissionCategoryID/0/32//Vanilla.Discussions.Announce',
                'Category/PermissionCategoryID/0/32//Vanilla.Comments.Add',
                'Category/PermissionCategoryID/0/32//Vanilla.Discussions.Close',
                'Category/PermissionCategoryID/0/32//Vanilla.Discussions.Delete',
                'Category/PermissionCategoryID/0/32//Vanilla.Discussions.Edit',
                'Category/PermissionCategoryID/0/32//Vanilla.Discussions.Sink',
                'Category/PermissionCategoryID/0/32//Vanilla.Discussions.View'
            ])
        ]);

        $body = $r->getBody();
        $category = $body['Category'];
        $this->assertArrayHasKey('CategoryID', $category);

        $this->setRestrictedCategoryID($category['CategoryID']);
    }

    /**
     * Test that a photo can be saved to a user.
     *
     * @param array $admin An admin user with permission to add a photo.
     * @param array $user The user to test against.
     * @depends testAddAdminUser
     * @depends testRegisterBasic
     * @large
     */
    public function testSetPhoto($admin, $user) {
        $this->api()->setUser($admin);

        $photo = 'http://example.com/u.gif';
        $r = $this->api()->post('/profile/edit.json?userid='.$user['UserID'], ['Photo' => $photo]);

        $dbUser = $this->api()->queryUserKey($user['UserID'], true);
        $this->assertSame($photo, $dbUser['Photo']);
    }

    /**
     * Test an invalid photo URL on a user.
     *
     * @param array $admin The user that will set the photo.
     * @param array $user The user to test against.
     * @depends testAddAdminUser
     * @depends testRegisterBasic
     * @expectedException \Exception
     * @expectedExceptionMessage Invalid photo URL.
     * @large
     */
    public function testSetInvalidPhoto($admin, $user) {
        $this->api()->setUser($admin);

        $photo = 'javascript: alert("Xss");';
        $r = $this->api()->post('/profile/edit.json?userid='.$user['UserID'], ['Photo' => $photo]);

        $dbUser = $this->api()->queryUserKey($user['UserID'], true);
        $this->assertSame($photo, $dbUser['Photo']);
    }

    /**
     * Test a permission error when adding a photo.
     *
     * @param array $user The user to test against.
     * @depends testRegisterBasic
     * @large
     */
    public function testSetPhotoPermission($user) {
        $this->api()->setUser($user);

        $dbUser = $this->api()->queryUserKey($user['UserID'], true);

        $photo = 'http://foo.com/bar.png';
        $r = $this->api()->post('/profile/edit.json?userid='.$user['UserID'], ['Photo' => $photo]);

        $dbUser2 = $this->api()->queryUserKey($user['UserID'], true);
        $this->assertNotEquals($photo, $dbUser2['Photo']);
        $this->assertSame($dbUser['Photo'], $dbUser2['Photo']);
    }

    /**
     * Test setting an uploaded photo that isn't a valid URL.
     *
     * @param array $user The user to test against.
     * @depends testRegisterBasic
     * @large
     */
    public function testSetPhotoPermissionLocal($user) {
        $this->api()->setUser($user);

        $dbUser = $this->api()->queryUserKey($user['UserID'], true);

        // This is a valid upload URL and should be allowed.
        $photo = 'userpics/679/FPNH7GFCMGBA.jpg';
        $this->assertNotEquals($dbUser['Photo'], $photo);
        $r = $this->api()->post('/profile/edit.json?userid='.$user['UserID'], ['Photo' => $photo]);

        $dbUser2 = $this->api()->queryUserKey($user['UserID'], true);
        $this->assertSame($photo, $dbUser2['Photo']);
        $this->assertNotEquals($dbUser['Photo'], $dbUser2['Photo']);
    }

    /**
     * Test that the APIv0 can actually send a correctly formatted user cookie.
     *
     * @depends testRegisterBasic
     * @large
     */
    public function testUserCookie() {
        $testUser = $this->getTestUser();
        $this->api()->setUser($testUser);
        $profile = $this->api()->get('/profile.json');

        $user = $profile['Profile'];
        $this->assertEquals($testUser['UserID'], $user['UserID']);
    }

    /**
     * Test posting a discussion.
     *
     * @depends testRegisterBasic
     * @large
     * @return array Single discussion.
     */
    public function testPostDiscussion() {
        $api = $this->api();
        $api->setUser($this->getTestUser());

        $discussion = [
            'CategoryID' => 1,
            'Name' => 'SmokeTest::testPostDiscussion()',
            'Body' => 'Test '.date('r'),
            'Format' => 'Text'
        ];

        $r = $api->post(
            '/post/discussion.json',
            $discussion
        );

        $postedDiscussion = $r->getBody();
        $postedDiscussion = $postedDiscussion['Discussion'];
        $this->assertArraySubset($discussion, $postedDiscussion);

        return $postedDiscussion;
    }

    /**
     * Test posting a single comment.
     *
     * @throws \Exception Throws an exception when there are no discussions.
     * @depends testPostDiscussion
     * @large
     */
    public function testPostComment() {
        $this->api()->setUser($this->getTestUser());

        $discussions = $this->api()->get('/discussions.json')->getBody();
        $discussions = val('Discussions', $discussions);
        if (empty($discussions)) {
            throw new \Exception("There are no discussions to post to.");
        }
        $discussion = reset($discussions);


        $comment = [
            'DiscussionID' => $discussion['DiscussionID'],
            'Body' => 'SmokeTest->testPostComment() '.date('r'),
            'Format' => 'Text'
        ];

        $r = $this->api()->post(
            '/post/comment.json',
            $comment
        );

        $postedComment = $r->getBody();
        $postedComment = $postedComment['Comment'];
        $this->assertArraySubset($comment, $postedComment);
    }

    /**
     * Test posting a discussion in a restricted category.
     *
     * @depends testCreateRestrictedCategory
     * @expectedException \Exception
     * @expectedExceptionMessage You do not have permission to post in this category.
     * @large
     */
    public function testPostRestrictedDiscussion() {
        $categoryID = $this->getRestrictedCategoryID();

        if (!is_numeric($categoryID)) {
            throw new \Exception('Invalid restricted category ID.');
        }

        $api = $this->api();
        $api->setUser($this->getTestUser());

        $discussion = [
            'CategoryID' => $categoryID,
            'Name' => 'SmokeTest::testPostRestrictedDiscussion()',
            'Body' => 'Test '.date('r')
        ];

        $api->post(
            '/post/discussion.json',
            $discussion
        );
    }

    /**
     * Test saving a draft.
     *
     * @depends testRegisterBasic
     * @return array $postedDraft
     * @large
     */
    public function testSaveDraft() {
        $api = $this->api();
        $api->setUser($this->getTestUser());

        $draft = [
            'DiscussionID' => '',
            'DraftD' => 0,
            'CategoryID' => 1,
            'Name' => 'Draft Test',
            'Format' => 'Markdown',
            'Body' => 'Test posting a new draft',
            'DeliveryType' => 'VIEW',
            'DeliveryMethod' => 'JSON',
            'Save Draft' => 'Save Draft',

        ];

        $r = $api->post(
            '/post/discussion.json',
            $draft
        );

        $responseBody = $r->getBody();
        $statusCode = $r->getStatusCode();
        $this->assertEquals(200, $statusCode);

        $draftModel = new \DraftModel();
        $postedDraft = $draftModel->getWhere(['DraftID' => $responseBody['DraftID']])->firstRow(DATASET_TYPE_ARRAY);

        $this->assertEquals($postedDraft['Name'], $draft['Name']);
        $this->assertEquals($postedDraft['Body'], $draft['Body']);
        $this->assertEquals($postedDraft['CategoryID'], $draft['CategoryID']);

        return $postedDraft;
    }

    /**
     * Test posting a Discussion from a Draft.
     *
     * @depends testRegisterBasic
     * @large
     */
    public function testPostDiscussionFromDraft() {
        $api = $this->api();
        $api->setUser($this->getTestUser());

        $draft = $this->testSaveDraft();

        $discussion = [
            'DraftID' => $draft['DraftID'],
            'CategoryID' => $draft['CategoryID'],
            'Name' => $draft['Name'],
            'Body' => $draft['Body'],
            'Format' => 'Text'
        ];

        $r2 = $api->post(
            "/post/editdiscussion/0/{$draft['DraftID']}.json",
            $discussion
        );
        $statusCode = $r2->getStatusCode();
        $this->assertEquals(200, $statusCode);

        $postedDiscussion = $r2->getBody();
        $postedDiscussion = $postedDiscussion['Discussion'];
        $this->assertEquals($discussion['Name'], $postedDiscussion['Name']);
        $this->assertEquals($discussion['Body'], $postedDiscussion['Body']);
        $this->assertEquals($discussion['CategoryID'], $postedDiscussion['CategoryID']);
    }

    /**
     * Delete a Draft.
     *
     * @depends testRegisterBasic
     * @large
     */
    public function testDeleteDraft() {
        $api = $this->api();
        $api->setUser($this->getTestUser());
        $user = $api->getUser();

        $draft = $this->testSaveDraft();

        $r2 = $api->get("drafts/delete/{$draft['DraftID']}/{$user['tk']}");
        $statusCode2 = $r2->getStatusCode();
        $this->assertEquals(200, $statusCode2);
    }

    /**
     * Test modifying a category of a draft.
     *
     * @depends testRegisterBasic
     * @large
     */
    public function testModifyDraftCategory() {
        $api = $this->api();
        $api->setUser($this->getTestUser());
        $category = $this->createCategory('Modify Draft', 'modifydraft');

        $draft =  $this->testSaveDraft();

        $draftUpdate = [
            'CategoryID' =>  $draft['CategoryID'],
            'DiscussionID' => $draft['DiscussionID'],
            'DraftID' => $draft['DraftID'],
            'CategoryID' => $category['Category']['CategoryID'],
            'Name' => $draft['Name'],
            'Format' => 'Markdown',
            'Body' => $draft['Body'],
            'DeliveryType' => 'VIEW',
            'DeliveryMethod' => 'JSON',
            'Save Draft' => 'Save Draft',
        ];

        $draftModel = new \DraftModel();
        $r = $api->post("/post/editdiscussion/0/{$draft['DraftID']}.json", $draftUpdate);
        $responseBody = $r->getBody();
        $modifiedDraft = $draftModel->getWhere(['DraftID' => $responseBody['DraftID']])->firstRow(DATASET_TYPE_ARRAY);

        $this->assertEquals($category['Category']['CategoryID'], $modifiedDraft['CategoryID']);
    }

    /**
     * Test saving a comment draft
     *
     * @depends testRegisterBasic
     * @return array $postedComment
     * @large
     */
    public function testSavingCommentDraft() {
        $api = $this->api();
        $api->setUser($this->getTestUser());

        $discussion = $this->testPostDiscussion();
        $discussionID = $discussion['DiscussionID'];

        $comment = [
            'DiscussionID' => $discussion['DiscussionID'],
            'CommentID' => '',
            'DraftID' => '',
            'Format' => 'Markdown',
            'Body' => 'Test comment draft',
            'DeliveryType' => 'VIEW',
            'DeliveryMethod' => 'JSON',
            'Type' => 'Draft',
            'LastCommentID' => 0,
        ];

        $r = $api->post("/post/comment/?discussionid={$discussionID}.json", $comment);
        $responseCode = $r->getStatusCode();
        $this->assertEquals(200, $responseCode);
        $responseBody = $r->getBody();

        $draftModel = new \DraftModel();
        $postedComment = $draftModel->getWhere(['DraftID' => $responseBody['DraftID']])->firstRow(DATASET_TYPE_ARRAY);
        $this->assertEquals($postedComment['DiscussionID'], $comment['DiscussionID']);
        $this->assertEquals($postedComment['Body'], $comment['Body']);

        return $postedComment;
    }

    /**
     * Test posting a comment draft
     *
     * @depends testRegisterBasic
     * @large
     */
    public function testPostCommentFromDraft() {
        $api = $this->api();
        $api->setUser($this->getTestUser());

        $draft = $this->testSavingCommentDraft();

        $postComment = [
            'DiscussionID' => $draft['DiscussionID'],
            'CommentID' => '',
            'DraftID' => $draft['DraftID'],
            'Format' => 'Markdown',
            'Body' => 'Test comment draft',
            'DeliveryType' => 'VIEW',
            'DeliveryMethod' => 'JSON',
            'Type' => 'Post',
            'LastCommentID' => 0,
        ];

        $r1 = $api->post("/post/comment/?discussionid={$draft['DiscussionID']}.json", $postComment);
        $responseCode1 = $r1->getStatusCode();
        $this->assertEquals(200, $responseCode1);
        $responseBody1 = $r1->getBody();

        $commentID = $responseBody1['CommentID'];
        $r2 = $api->post("/post/comment2.json?commentid={$commentID}&inserted=1");
        $responseCode2 = $r2->getStatusCode();
        $this->assertEquals(200, $responseCode2);

        $commentModel = new \CommentModel();
        $dbComment = $commentModel->getWhere(['CommentID' => $commentID ])->firstRow(DATASET_TYPE_ARRAY);
        $this->assertEquals($dbComment['DiscussionID'], $postComment['DiscussionID']);
        $this->assertEquals($dbComment['Body'], $postComment['Body']);
    }

    /**
     * Create a category for testing.
     *
     * @param string $name Category name.
     * @param  string $url Category url.
     * @return array $category
     */
    private function createCategory($name = null, $url = null) {
        $api = $this->api();
        $admin = $api->querySystemUser(true);
        $api->setUser($admin);

        $r = $this->api()->post('/vanilla/settings/addcategory.json', [
            'Name' => 'Test Category '.$name,
            'UrlCode' => 'test'.$url,
            'DisplayAs' => 'Discussions',
        ]);

        $category = $r->getBody();
        return $category;
    }


    /**
     * Test viewing a restricted category.
     *
     * @depends testCreateRestrictedCategory
     * @expectedException \Exception
     * @expectedExceptionMessage You don't have permission to do that.
     * @large
     */
    public function testViewRestrictedCategory() {
        $categoryID = $this->getRestrictedCategoryID();

        if (!is_numeric($categoryID)) {
            throw new \Exception('Invalid restricted category ID.');
        }

        $api = $this->api();
        $api->setUser($this->getTestUser());

        $api->get("categories.json?CategoryIdentifier={$categoryID}");
    }

    /**
     * Test adding a bookmark to a discussion.
     *
     * @depends testRegisterBasic
     * @depends testPostDiscussion
     * @large
     */
    public function testDiscussionAddBookMark() {
        $api = $this->api();
        $api->setUser($this->getTestUser());
        $user = $api->getUser();

        $discussion = $this->testPostDiscussion();
        $discussionID = val('DiscussionID', $discussion);
        $r = $api->post("/discussion/bookmark/{$discussionID}/{$user['tk']}");
        $statusCode = $r->getStatusCode();
        $this->assertEquals(200, $statusCode);

        $postedBookMark = $this->api()->get("/discussion/{$discussionID}.json")->getBody();
        $isBookMarked = $postedBookMark['Discussion']['Bookmarked'];
        $this->assertEquals(1, $isBookMarked);
    }

    /**
     * Test removing a bookmark from a discussion.
     *
     * @depends testRegisterBasic
     * @depends testPostDiscussion
     * @large
     */
    public function testRemoveDiscussionBookMark() {
        $api = $this->api();
        $api->setUser($this->getTestUser());
        $user = $api->getUser();


        $discussion = $this->testPostDiscussion();
        $discussionID = val('DiscussionID', $discussion);

        $r = $api->post("/discussion/bookmark/{$discussionID}/{$user['tk']}");
        $statusCode = $r->getStatusCode();
        $this->assertEquals(200, $statusCode);

        $bookMarkedDiscussion = $this->api()->get("/discussion/{$discussionID}.json")->getBody();
        $isBookMarked = $bookMarkedDiscussion['Discussion']['Bookmarked'];
        $this->assertEquals(1, $isBookMarked);

        $r = $api->post("/discussion/bookmark/{$discussionID}/{$user['tk']}");
        $statusCode = $r->getStatusCode();
        $this->assertEquals(200, $statusCode);

        $unBookMarkedDiscussion = $this->api()->get("/discussion/{$discussionID}.json")->getBody();
        $isNotBookMarked = $unBookMarkedDiscussion['Discussion']['Bookmarked'];
        $this->assertEquals(0, $isNotBookMarked);
    }
}
