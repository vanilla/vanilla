<?php
/**
 * @author Ryan Perry <ryan.p@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

use DiscussionModel;

/**
 * Test the /api/v2/discussions endpoints.
 */
class CommentsTest extends AbstractResourceTest {
    use AssertLoggingTrait, TestPrimaryKeyRangeFilterTrait, TestSortingTrait;

    /**
     * {@inheritdoc}
     */
    public function __construct($name = null, array $data = [], $dataName = '') {
        $this->baseUrl = '/comments';
        $this->resourceName = 'comment';
        $this->record += ['discussionID' => 1];
        $this->sortFields = ['dateInserted', 'commentID'];

        parent::__construct($name, $data, $dataName);
    }

    /**
     * @inheritdoc
     */
    public function indexUrl() {
        $indexUrl = $this->baseUrl;
        $indexUrl .= '?'.http_build_query(['discussionID' => 1]);
        return $indexUrl;
    }

    /**
     * Verify that custom category permissions don't wipe out access to all comments.
     */
    public function testCustomCategoryPermissions() {
        // Default discussion ID. This is created during install.
        $discussionID = 1;

        // Create a new user for this test. It will receive the default member role.
        $username = substr(__FUNCTION__, 0, 20);
        $user = $this->api()->post('users', [
            'name' => $username,
            'email' => $username.'@example.com',
            'password' => 'vanilla'
        ])->getBody();
        $this->assertCount(1, $user['roles'], 'User has too many default roles.');
        $roleID = $user['roles'][0]['roleID'];

        // Switch to the user we just created and comment on the default discussion.
        $this->api()->setUserID($user['userID']);
        $this->api()->post('comments', [
            'body' => 'Hello world.',
            'format' => 'text',
            'discussionID' => $discussionID
        ]);
        $comments = $this->api()->get('comments', [
            'discussionID' => $discussionID
        ])->getBody();

        // Switch back to the admin user and add a new category.
        $this->api()->setUserID(self::$siteInfo['adminUserID']);
        $category = $this->api()->post('categories', [
            'name' => __FUNCTION__,
            'urlcode' => strtolower(__FUNCTION__)
        ])->getBody();

        // Update the permissions of the default member role to revoke permissions to the new category.
        $this->api()->patch("roles/{$roleID}/permissions", [[
            'id' => $category['categoryID'],
            'type' => 'category',
            'permissions' => [
                'comments.add' => false,
                'comments.delete' => false,
                'comments.edit' => false,
                'discussions.add' => false,
                'discussions.manage' => false,
                'discussions.moderate' => false,
                'discussions.view' => false
            ]
        ]]);

        // Switch back to the user we created and make sure they can still see the same comments as before.
        $this->api()->setUserID($user['userID']);
        DiscussionModel::categoryPermissions(false, true);
        $updatedComments = $this->api()->get('comments', [
            'discussionID' => $discussionID
        ])->getBody();

        $this->assertEquals($comments, $updatedComments);
    }
}
