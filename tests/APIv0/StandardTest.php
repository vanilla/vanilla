<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2014 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\APIv0;


class StandardTest extends BaseTest {

    /**
     * @var array
     */
    protected static $testUser;

    /**
     * Get the testUser.
     *
     * @return array Returns the testUser.
     */
    public function getTestUser() {
        return self::$testUser;
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

        $siteUser['tk'] = $this->api()->getTK($siteUser['UserID']);
        $this->setTestUser($siteUser);
        return $siteUser;
    }



    /**
     * Test adding an admin user.
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

        $dbUser['tk'] = $this->api()->getTK($dbUser['UserID']);
        return $dbUser;
    }

    /**
     * Test that a photo can be saved to a user.
     *
     * @param array $admin An admin user with permission to add a photo.
     * @param array $user The user to test against.
     * @depends testAddAdminUser
     * @depends testRegisterBasic
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
     */
    public function testSetPhotoPermission($user) {
        $this->api()->setUser($user);

        $dbUser = $this->api()->queryUserKey($user['UserID'], true);

        $photo = $user['Photo'].'.png';
        $r = $this->api()->post('/profile/edit.json?userid='.$user['UserID'], ['Photo' => $photo]);

        $dbUser2 = $this->api()->queryUserKey($user['UserID'], true);
        $this->assertSame($dbUser['Photo'], $dbUser2['Photo']);
    }

    /**
     * Test that the APIv0 can actually send a correctly formatted user cookie.
     *
     * @depends testRegisterBasic
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
     */
    public function testPostDiscussion() {
        $api = $this->api();
        $api->setUser($this->getTestUser());

        $discussion = [
            'CategoryID' => 1,
            'Name' => 'StandardTest::testPostDiscussion()',
            'Body' => 'Test '.date('r')
        ];

        $r = $api->post(
            '/post/discussion.json',
            $discussion
        );

        $postedDiscussion = $r->getBody();
        $postedDiscussion = $postedDiscussion['Discussion'];
        $this->assertArraySubset($discussion, $postedDiscussion);
    }

    /**
     * Test posting a single comment.
     *
     * @throws \Exception Throws an exception when there are no discussions.
     * @depends testPostDiscussion
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
            'Body' => 'StandardTest->testPostComment() '.date('r')
        ];

        $r = $this->api()->post(
            '/post/comment.json',
            $comment
        );

        $postedComment = $r->getBody();
        $postedComment = $postedComment['Comment'];
        $this->assertArraySubset($comment, $postedComment);
    }
}
