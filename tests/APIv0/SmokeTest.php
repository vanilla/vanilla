<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv0;

use VanillaTests\SiteTestCase;

/**
 * Test some basic Vanilla functionality to make sure nothing is horribly broken.
 */
class SmokeTest extends SiteTestCase
{
    /** @var  int */
    protected static $restrictedCategoryID;

    /**
     * @var array
     */
    protected static $testUser;

    /**
     * @var \Gdn_Configuration
     */
    private $config;

    /**
     * {@inheritDoc}
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->container()->call(function (\Gdn_Configuration $config) {
            $this->config = $config;
        });
        $this->config->saveToConfig([
            "Garden.User.ValidationLength" => "{3,50}",
        ]);
        $this->createUserFixtures();
    }

    /**
     * Get the ID of the restricted category.
     *
     * @return int
     */
    public function getRestrictedCategoryID()
    {
        return static::$restrictedCategoryID;
    }

    /**
     * Get the testUser.
     *
     * @return array Returns the testUser.
     */
    public function getTestUser()
    {
        return self::$testUser;
    }

    /**
     * Set the ID of the restricted category.
     *
     * @param int $categoryID
     * @return $this
     */
    public function setRestrictedCategoryID($categoryID)
    {
        static::$restrictedCategoryID = $categoryID;
        return $this;
    }

    /**
     * Set the testUser.
     *
     * @param array $testUser The user to set.
     * @return $this
     * @see APIv0::queryUserKey()
     */
    public function setTestUser($testUser)
    {
        static::$testUser = $testUser;
        return $this;
    }

    /**
     * Test registering a user with the basic method.
     */
    public function testRegisterBasic()
    {
        $this->config->saveToConfig([
            "Garden.Registration.Method" => "Basic",
            "Garden.Registration.ConfirmEmail" => false,
            "Garden.Registration.SkipCaptcha" => true,
        ]);

        $user = [
            "Name" => "frank",
            "Email" => "frank@example.com",
            "Password" => "123fr@nkwantsin+NewPassword",
            "PasswordMatch" => "123fr@nkwantsin+NewPassword",
            "Gender" => "m",
            "TermsOfService" => "1",
        ];

        // Register the user.
        $body = $this->bessy()->postJsonData("/entry/register.json", $user);
        $this->assertSame("Basic", $body["Method"]);

        // Look for the user in the database.
        $dbUser = (array) $this->userModel->getByUsername($user["Name"]);
        $this->assertSame($user["Email"], $dbUser["Email"]);
        $this->assertSame($user["Gender"], $dbUser["Gender"]);

        // Look up the user for confirmation.
        $siteUser = $this->bessy()->getJsonData("/profile.json", ["username" => $user["Name"]]);
        $siteUser = $siteUser["Profile"];

        $this->assertEquals($user["Name"], $siteUser["Name"]);

        $this->setTestUser($siteUser);
        return $siteUser;
    }

    /**
     * Test adding an admin user.
     */
    public function testAddAdminUser()
    {
        $this->getSession()->start($this->adminID);

        $adminUser = [
            "Name" => "Admin",
            "Email" => "admin@example.com",
            "Password" => randomString(\Gdn::config("Garden.Password.MinLength")),
        ];

        // Get the admin roles.
        $adminRoleID = $this->roleID(self::ROLE_ADMIN);
        $this->assertNotEmpty($adminRoleID);
        $adminUser["RoleID"] = [$adminRoleID];

        $b = $this->bessy()->postJsonData("/user/add.json", $adminUser);

        // Query the user in the database.
        $dbUser = (array) $this->userModel->getByUsername("Admin");

        // Query the admin role.
        $this->assertUserHasRoles($dbUser["UserID"], [$adminRoleID]);

        return $dbUser;
    }

    /**
     * Test that a category with restricted permissions can be created.
     *
     * @large
     */
    public function testCreateRestrictedCategory()
    {
        $body = $this->bessy()->postJsonData(
            "/vanilla/settings/addcategory.json",
            [
                "Name" => "Moderators Only",
                "UrlCode" => "moderators-only",
                "DisplayAs" => "Discussions",
                "CustomPermissions" => 1,
                "Permission" => [
                    "Category/PermissionCategoryID/0/32//Vanilla.Comments.Add",
                    "Category/PermissionCategoryID/0/32//Vanilla.Comments.Delete",
                    "Category/PermissionCategoryID/0/32//Vanilla.Comments.Edit",
                    "Category/PermissionCategoryID/0/32//Vanilla.Discussions.Add",
                    "Category/PermissionCategoryID/0/32//Vanilla.Discussions.Announce",
                    "Category/PermissionCategoryID/0/32//Vanilla.Comments.Add",
                    "Category/PermissionCategoryID/0/32//Vanilla.Discussions.Close",
                    "Category/PermissionCategoryID/0/32//Vanilla.Discussions.Delete",
                    "Category/PermissionCategoryID/0/32//Vanilla.Discussions.Edit",
                    "Category/PermissionCategoryID/0/32//Vanilla.Discussions.Sink",
                    "Category/PermissionCategoryID/0/32//Vanilla.Discussions.View",
                ],
            ],
            ["content-type" => "application/json"]
        );

        $category = $body["Category"];
        $this->assertArrayHasKey("CategoryID", $category);

        $this->setRestrictedCategoryID($category["CategoryID"]);
    }

    /**
     * Test that a photo can be saved to a user.
     */
    public function testSetPhoto()
    {
        $this->getSession()->start($this->adminID);

        $photo = "http://example.com/u.gif";
        $r = $this->bessy()->postJsonData("/profile/edit.json?userid=" . $this->memberID, ["Photo" => $photo]);

        $dbUser = $this->userModel->getID($this->memberID, DATASET_TYPE_ARRAY);
        $this->assertSame($photo, $dbUser["Photo"]);
    }

    /**
     * Test an invalid photo URL on a user.
     */
    public function testSetInvalidPhoto()
    {
        $this->getSession()->start($this->adminID);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Invalid photo URL.");

        $photo = 'javascript: alert("Xss");';
        $r = $this->bessy()->postJsonData("/profile/edit.json?userid=" . $this->memberID, ["Photo" => $photo]);
    }

    /**
     * Test a permission error when adding a photo.
     */
    public function testSetPhotoPermission()
    {
        $this->getSession()->start($this->memberID);

        $dbUser = $this->userModel->getID($this->memberID, DATASET_TYPE_ARRAY);

        $photo = "http://foo.com/bar.png";
        $r = $this->bessy()->post("/profile/edit.json?userid=" . $this->memberID, ["Photo" => $photo]);

        $dbUser2 = $this->userModel->getID($this->memberID, DATASET_TYPE_ARRAY);
        $this->assertNotEquals($photo, $dbUser2["Photo"]);
        $this->assertSame($dbUser["Photo"], $dbUser2["Photo"]);
    }

    /**
     * Test setting an uploaded photo that isn't a valid URL.
     */
    public function testSetPhotoPermissionLocal()
    {
        $this->getSession()->start($this->memberID);

        $dbUser = $this->userModel->getID($this->memberID, DATASET_TYPE_ARRAY);

        // This is a valid upload URL and should be allowed.
        $photo = "userpics/679/FPNH7GFCMGBA.jpg";
        $this->assertNotEquals($dbUser["Photo"], $photo);
        $r = $this->bessy()->post("/profile/edit.json?userid=" . $this->memberID, ["Photo" => $photo]);

        $dbUser2 = $this->userModel->getID($this->memberID, DATASET_TYPE_ARRAY);
        $this->assertSame($photo, $dbUser2["Photo"]);
        $this->assertNotEquals($dbUser["Photo"], $dbUser2["Photo"]);
    }

    /**
     * Test posting a discussion.
     *
     * @return array Single discussion.
     */
    public function testPostDiscussion()
    {
        $this->getSession()->start($this->memberID);

        $discussion = [
            "CategoryID" => 1,
            "Name" => "SmokeTest::testPostDiscussion()",
            "Body" => "Test " . date("r"),
            "Format" => "Text",
        ];

        $postedDiscussion = $this->bessy()->postJsonData("/post/discussion.json", $discussion);

        $postedDiscussion = $postedDiscussion["Discussion"];
        $this->assertEquals($discussion, array_intersect_assoc($discussion, $postedDiscussion));

        return $postedDiscussion;
    }

    /**
     * Test posting a single comment.
     *
     * @depends testPostDiscussion
     */
    public function testPostComment()
    {
        $this->getSession()->start($this->memberID);

        $discussions = $this->bessy()->getJsonData("/discussions.json");
        $discussions = $discussions["Discussions"];
        if (empty($discussions)) {
            throw new \Exception("There are no discussions to post to.");
        }
        $discussion = reset($discussions);

        $comment = [
            "DiscussionID" => $discussion["DiscussionID"],
            "Body" => "SmokeTest->testPostComment() " . date("r"),
            "Format" => "Text",
        ];

        $postedComment = $this->bessy()->postJsonData("/post/comment.json", $comment);

        $postedComment = $postedComment["Comment"];
        $this->assertEquals($comment, array_intersect_assoc($comment, $postedComment));
    }

    /**
     * Test posting a discussion in a restricted category.
     *
     * @depends testCreateRestrictedCategory
     */
    public function testPostRestrictedDiscussion()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("You do not have permission to post in this category.");

        $categoryID = $this->getRestrictedCategoryID();

        if (!is_numeric($categoryID)) {
            throw new \Exception("Invalid restricted category ID.");
        }

        $this->getSession()->start($this->memberID);

        $discussion = [
            "CategoryID" => $categoryID,
            "Name" => "SmokeTest::testPostRestrictedDiscussion()",
            "Body" => "Test " . date("r"),
        ];

        $this->bessy()->post("/post/discussion.json", $discussion);
    }

    /**
     * Test saving a draft.
     *
     * @return array $postedDraft
     */
    public function testSaveDraft()
    {
        $this->getSession()->start($this->memberID);

        $draft = [
            "DiscussionID" => "",
            "DraftD" => 0,
            "CategoryID" => 1,
            "Name" => "Draft Test",
            "Format" => "Markdown",
            "Body" => "Test posting a new draft",
            "DeliveryType" => "VIEW",
            "DeliveryMethod" => "JSON",
            "Save_Draft" => "Save Draft",
        ];

        $responseBody = $this->bessy()->postJsonData("/post/discussion.json", $draft);

        $draftModel = new \DraftModel();
        $postedDraft = $draftModel->getWhere(["DraftID" => $responseBody["DraftID"]])->firstRow(DATASET_TYPE_ARRAY);

        $this->assertEquals($postedDraft["Name"], $draft["Name"]);
        $this->assertEquals($postedDraft["Body"], $draft["Body"]);
        $this->assertEquals($postedDraft["CategoryID"], $draft["CategoryID"]);

        return $postedDraft;
    }

    /**
     * Test posting a Discussion from a Draft.
     */
    public function testPostDiscussionFromDraft()
    {
        $this->getSession()->start($this->memberID);

        $draft = $this->testSaveDraft();

        $discussion = [
            "DraftID" => $draft["DraftID"],
            "CategoryID" => $draft["CategoryID"],
            "Name" => $draft["Name"],
            "Body" => $draft["Body"],
            "Format" => "Text",
        ];

        $postedDiscussion = $this->bessy()->postJsonData(
            "/post/editdiscussion/0/{$draft["DraftID"]}.json",
            $discussion
        );

        $postedDiscussion = $postedDiscussion["Discussion"];
        $this->assertEquals($discussion["Name"], $postedDiscussion["Name"]);
        $this->assertEquals($discussion["Body"], $postedDiscussion["Body"]);
        $this->assertEquals($discussion["CategoryID"], $postedDiscussion["CategoryID"]);
    }

    /**
     * Delete a Draft.
     */
    public function testDeleteDraft()
    {
        $this->getSession()->start($this->memberID);

        $draft = $this->testSaveDraft();
        $tk = __FUNCTION__;
        $this->getSession()->transientKey($tk, false);

        $r2 = $this->bessy()->getJsonData("drafts/delete/{$draft["DraftID"]}/$tk");

        $draftModel = new \DraftModel();
        $draft = $draftModel->getID($draft["DraftID"]);
        $this->assertFalse($draft);
    }

    /**
     * Test modifying a category of a draft.
     *
     */
    public function testModifyDraftCategory()
    {
        $category = $this->createCategory("Modify Draft", "modifydraft");
        $draft = $this->testSaveDraft();

        $draftUpdate = [
            "CategoryID" => $category["Category"]["CategoryID"],
            "DiscussionID" => $draft["DiscussionID"],
            "DraftID" => $draft["DraftID"],
            "Name" => $draft["Name"],
            "Format" => "Markdown",
            "Body" => $draft["Body"],
            "DeliveryType" => "VIEW",
            "DeliveryMethod" => "JSON",
            "Save_Draft" => "Save Draft",
        ];

        $draftModel = new \DraftModel();
        $responseBody = $this->bessy()->postJsonData("/post/editdiscussion/0/{$draft["DraftID"]}.json", $draftUpdate);
        $modifiedDraft = $draftModel->getWhere(["DraftID" => $responseBody["DraftID"]])->firstRow(DATASET_TYPE_ARRAY);

        $this->assertEquals($category["Category"]["CategoryID"], $modifiedDraft["CategoryID"]);
    }

    /**
     * Test saving a comment draft
     *
     * @return array $postedComment
     * @large
     */
    public function testSavingCommentDraft()
    {
        $this->getSession()->start($this->memberID);

        $discussion = $this->testPostDiscussion();
        $discussionID = $discussion["DiscussionID"];

        $comment = [
            "DiscussionID" => $discussion["DiscussionID"],
            "CommentID" => "",
            "DraftID" => "",
            "Format" => "Markdown",
            "Body" => "Test comment draft",
            "DeliveryType" => "VIEW",
            "DeliveryMethod" => "JSON",
            "Type" => "Draft",
            "LastCommentID" => 0,
        ];

        $responseBody = $this->bessy()->postJsonData("/post/comment/?discussionid={$discussionID}.json", $comment);

        $draftModel = new \DraftModel();
        $postedComment = $draftModel->getWhere(["DraftID" => $responseBody["DraftID"]])->firstRow(DATASET_TYPE_ARRAY);
        $this->assertEquals($postedComment["DiscussionID"], $comment["DiscussionID"]);
        $this->assertEquals($postedComment["Body"], $comment["Body"]);

        return $postedComment;
    }

    /**
     * Test posting a comment draft
     */
    public function testPostCommentFromDraft()
    {
        $this->getSession()->start($this->memberID);

        $draft = $this->testSavingCommentDraft();

        $postComment = [
            "DiscussionID" => $draft["DiscussionID"],
            "CommentID" => "",
            "DraftID" => $draft["DraftID"],
            "Format" => "Markdown",
            "Body" => "Test comment draft",
            "DeliveryType" => "VIEW",
            "DeliveryMethod" => "JSON",
            "Type" => "Post",
            "LastCommentID" => 0,
        ];

        $responseBody1 = $this->bessy()->postJsonData(
            "/post/comment/?discussionid={$draft["DiscussionID"]}.json",
            $postComment
        );

        $commentID = $responseBody1["CommentID"];
        $r2 = $this->bessy()->post("/post/comment2.json?commentid={$commentID}&inserted=1");

        $commentModel = new \CommentModel();
        $dbComment = $commentModel->getWhere(["CommentID" => $commentID])->firstRow(DATASET_TYPE_ARRAY);
        $this->assertEquals($dbComment["DiscussionID"], $postComment["DiscussionID"]);
        $this->assertEquals($dbComment["Body"], $postComment["Body"]);
    }

    /**
     * Create a category for testing.
     *
     * @param string $name Category name.
     * @param  string $url Category url.
     * @return array $category
     */
    private function createCategory($name = null, $url = null)
    {
        $this->getSession()->start($this->adminID);

        $category = $this->bessy()->postJsonData("/vanilla/settings/addcategory.json", [
            "Name" => "Test Category " . $name,
            "UrlCode" => "test" . $url,
            "DisplayAs" => "Discussions",
        ]);

        return $category;
    }

    /**
     * Test viewing a restricted category.
     *
     * @depends testCreateRestrictedCategory
     */
    public function testViewRestrictedCategory()
    {
        $categoryID = $this->getRestrictedCategoryID();

        if (!is_numeric($categoryID)) {
            throw new \Exception("Invalid restricted category ID.");
        }

        $this->getSession()->start($this->memberID);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('You don\'t have permission to do that.');
        $this->bessy()->get("categories.json?CategoryIdentifier={$categoryID}");
    }

    /**
     * Test adding a bookmark to a discussion.
     */
    public function testDiscussionAddBookMark()
    {
        $discussion = $this->testPostDiscussion();
        $discussionID = val("DiscussionID", $discussion);
        $r = $this->bessy()->post("/discussion/bookmark/{$discussionID}");

        $postedBookMark = $this->bessy()->getJsonData("/discussion/{$discussionID}.json");
        $isBookMarked = $postedBookMark["Discussion"]["Bookmarked"];
        $this->assertEquals(1, $isBookMarked);
    }

    /**
     * Test removing a bookmark from a discussion.
     */
    public function testRemoveDiscussionBookMark()
    {
        $discussion = $this->testPostDiscussion();
        $discussionID = val("DiscussionID", $discussion);

        $r = $this->bessy()->post("/discussion/bookmark/{$discussionID}");

        $bookMarkedDiscussion = $this->bessy()->getJsonData("/discussion/{$discussionID}.json");
        $isBookMarked = $bookMarkedDiscussion["Discussion"]["Bookmarked"];
        $this->assertEquals(1, $isBookMarked);

        $r = $this->bessy()->post("/discussion/bookmark/{$discussionID}");

        $unBookMarkedDiscussion = $this->bessy()->getJsonData("/discussion/{$discussionID}.json");
        $isNotBookMarked = $unBookMarkedDiscussion["Discussion"]["Bookmarked"];
        $this->assertEquals(0, $isNotBookMarked);
    }
}
