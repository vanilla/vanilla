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
     * Test registering a user with the basic method.
     */
    public function testRegisterBasic() {
        $this->api()->saveToConfig([
            'Garden.Registration.Method' => 'Basic',
            'Garden.Registration.ConfirmEmail' => false
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

        // Look up the user for confirmation.
        $siteUser = $this->api()->get('/profile.json', ['username' => 'frank']);
        $siteUser = $siteUser['Profile'];

        $this->assertEquals($user['Name'], $siteUser['Name']);

        $siteUser['tk'] = $this->api()->getTK($siteUser['UserID']);
        $this->setTestUser($siteUser);
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
     * @param array $testUser
     * @return StandardTest Returns `$this` for fluent calls.
     */
    public function setTestUser($testUser) {
        static::$testUser = $testUser;
        return $this;
    }
}
