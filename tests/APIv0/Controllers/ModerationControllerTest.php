<?php
/**
 * Created by PhpStorm.
 * User: alexander
 * Date: 2018-09-27
 * Time: 11:31 AM
 */

namespace VanillaTests\Controllers;

use VanillaTests\APIv0\BaseTest;

class ModerationControllerTest extends BaseTest {
    protected static $testUser;

    protected $user;

    /**
     * @var array Array of categories holding initial/valid values for tests
     */
    protected static $categories = [];

    protected static $discussions = [];

    public static function setUpBeforeClass() {
        parent::setUpBeforeClass();
    }

    public function testModerationDiscussionMoveInintDB() {
        $this->api()->saveToConfig([
            'Garden.Registration.Method' => 'Basic',
            'Garden.Registration.ConfirmEmail' => false,
            'Garden.Registration.SkipCaptcha' => true,
        ]);

        self::$testUser = $this->addAdminUser();
        $this->api()->setUser(self::$testUser);

        self::$categories['cat1'] = $cat1 = $this->addCategory(['Name' => 'Root Cat 1',
            'UrlCode' => 'root-cat-1',
            'DisplayAs' => 'Discussions']);

        self::$categories['cat1_1'] = $cat1_1 = $this->addCategory(['Name' => 'Level2 Child 1 of Cat 1',
            'UrlCode' => 'cat-1-child-1',
            'ParentCategoryID' => $cat1['CategoryID'],
            'DisplayAs' => 'Discussions']);

        self::$categories['cat1_1_1'] = $cat1_1_1 = $this->addCategory(['Name' => 'Level3 Child 1-1-1',
            'UrlCode' => 'cat-1-child-1-1',
            'ParentCategoryID' => $cat1_1['CategoryID'],
            'DisplayAs' => 'Discussions']);

        self::$categories['cat1_2'] = $cat1_2 = $this->addCategory(['Name' => 'Level2 Child 2 of Cat 1',
            'UrlCode' => 'cat-1-child-2',
            'ParentCategoryID' => $cat1['CategoryID'],
            'DisplayAs' => 'Discussions']);

        self::$categories['cat1_2_1'] = $cat1_2_1 = $this->addCategory(['Name' => 'Level3 Child 1 of Cat 1-2',
            'UrlCode' => 'cat-1-child-2-1',
            'ParentCategoryID' => $cat1_2['CategoryID'],
            'DisplayAs' => 'Discussions']);

        self::$categories['cat2'] = $cat2 = $this->addCategory(['Name' => 'Root Cat 2',
            'UrlCode' => 'root-cat-2',
            'DisplayAs' => 'Discussions']);

        self::$categories['cat2_1'] = $cat2_1 = $this->addCategory(['Name' => 'Level2 Child 1 of Cat 2',
            'UrlCode' => 'cat-2-child-1',
            'ParentCategoryID' => $cat2['CategoryID'],
            'DisplayAs' => 'Discussions']);

        self::$categories['cat2_1_1'] = $cat2_1_1 = $this->addCategory(['Name' => 'Level3 Child 2-1-1',
            'UrlCode' => 'cat-2-child-1-1',
            'ParentCategoryID' => $cat2_1['CategoryID'],
            'DisplayAs' => 'Discussions']);

        self::$categories['cat2_2'] = $cat2_2 = $this->addCategory(['Name' => 'Level2 Child 2 of Cat 2',
            'UrlCode' => 'cat-2-child-2',
            'ParentCategoryID' => $cat2['CategoryID'],
            'DisplayAs' => 'Discussions']);

        self::$categories['cat2_2_1'] = $cat2_2_1 = $this->addCategory(['Name' => 'Level3 Child 1 of Cat 2-2',
            'UrlCode' => 'cat-2-child-2-1',
            'ParentCategoryID' => $cat2_2['CategoryID'],
            'DisplayAs' => 'Discussions']);

        self::$categories['cat2_2_1_1'] = $cat2_2_1_1 = $this->addCategory(['Name' => 'Level4 Child 1 of Cat 2-2-1',
            'UrlCode' => 'cat-2-child-2-1-1',
            'ParentCategoryID' => $cat2_2_1['CategoryID'],
            'DisplayAs' => 'Discussions']);

        self::$categories['cat2_2_1_1_1'] = $cat2_2_1_1_1 = $this->addCategory(['Name' => 'Level5 Child 1 of Cat 2-2-1-1',
            'UrlCode' => 'cat-2-child-2-1-1-1',
            'ParentCategoryID' => $cat2_2_1_1['CategoryID'],
            'DisplayAs' => 'Discussions']);

        self::$categories['cat2_2_1_1_1_1'] = $cat2_2_1_1_1_1 = $this->addCategory(['Name' => 'Level6 Child 1 of Cat 2-2-1-1-1',
            'UrlCode' => 'cat-2-child-2-1-1-1-1',
            'ParentCategoryID' => $cat2_2_1_1_1['CategoryID'],
            'DisplayAs' => 'Discussions']);

        self::$discussions['d1_c1-1-1'] = $discussion = $this->addDiscussion([
            'CategoryID' => $cat1_1_1['CategoryID'],
            'Name' => 'Discussion 1 of cat1-1-1',
            'Body' => 'Test '.rand(1,9999999999)
        ], 'cat1_1_1');


        $comment = $this->addComment([
            'DiscussionID' => $discussion['DiscussionID'],
            'Body' => 'Moderation controller test. LINE: '.__LINE__.' DATE: '.date('r')
        ], 'cat1_1_1');

        self::$discussions['d2_c1-1-1'] = $discussion = $this->addDiscussion([
            'CategoryID' => $cat1_1_1['CategoryID'],
            'Name' => 'Discussion 2 of cat1-1-1',
            'Body' => 'Test '.rand(1,9999999999)
        ], 'cat1_1_1');



        self::$discussions['d1_c2_2_1_1_1_1'] = $discussion = $this->addDiscussion([
            'CategoryID' => $cat2_2_1_1_1_1['CategoryID'],
            'Name' => 'Discussion 1 of cat2_2_1_1_1_1',
            'Body' => 'Test '.rand(1,9999999999)
        ], 'cat2_2_1_1_1_1');


        self::$discussions['d2_c2_2_1_1_1_1'] = $discussion = $this->addDiscussion([
            'CategoryID' => $cat2_2_1_1_1_1['CategoryID'],
            'Name' => 'Discussion 2 of cat2_2_1_1_1_1',
            'Body' => 'Test '.rand(1,9999999999)
        ], 'cat2_2_1_1_1_1');

        $comment = $this->addComment([
            'DiscussionID' => $discussion['DiscussionID'],
            'Body' => 'Moderation controller test. LINE: '.__LINE__.' DATE: '.date('r')
        ], 'cat2_2_1_1_1_1');


        $this->assertTrue( true);
    }

    protected static function updateValidValues(string $catKey, string $fieldToUpdate, $newValue, bool $recursively = true) {
        do {
            $continue = false;
            switch ($newValue) {
                case '++':
                    self::$categories[$catKey][$fieldToUpdate]++;
                    break;
                case '--':
                    self::$categories[$catKey][$fieldToUpdate]--;
                    break;
                default:
                    self::$categories[$catKey][$fieldToUpdate] = $newValue;
            }

            if ($recursively) {
                if (($pos = strrpos($catKey, '_')) > 0) {
                    $continue = true;
                    $catKey = substr($catKey, 0, $pos);
                }
            }

        } while ($continue);
    }

    protected static function updateValidValuesOnMoveDiscussion(array $discussion, string $srcCategoryKey, string $destCategoryKey, bool $updateRecent = true, array $srcDiscussionToUpdate = []) {

        // Right now CountDiscussions field is not updated at all  - which is wrong
        // @todo We need to uncomment next 2 lines when bug is fixed
        // self::updateValidValues($destCategoryKey,'CountDiscussions','++');
        // self::updateValidValues($srcCategoryKey,'CountDiscussions','--');
        self::updateValidValues($destCategoryKey , 'CountAllDiscussions', '++');
        self::updateValidValues($srcCategoryKey , 'CountAllDiscussions', '--');
        if ($updateRecent) {
            self::updateValidValues($destCategoryKey , 'LastDateInserted', $discussion['DateInserted']);
            self::updateValidValues($destCategoryKey , 'LastDiscussionID', $discussion['DiscussionID']);
            //self::updateValidValues($srcCategoryKey , 'LastDateInserted', ($srcDiscussion['DateInserted'] ?? null));
            //self::updateValidValues($srcCategoryKey , 'LastDiscussionID', ($srcDiscussion['DiscussionID'] ?? null));
        }

    }

    protected static function addDiscussion(array $discussion, string $catKey) {
        $r = self::$api->post(
            '/post/discussion.json',
            $discussion
        );
        if ($r->getStatusCode() != 200) {
            throwException('Failed to create new discussion: ' . json_encode($discussion));
        }
        $body = $r->getBody();
        if (!empty($catKey)) {
            self::updateValidValues($catKey , 'CountDiscussions', '++', false);
            self::updateValidValues($catKey , 'CountAllDiscussions', '++');
            self::updateValidValues($catKey , 'LastDateInserted', $body['Discussion']['DateInserted']);
            self::updateValidValues($catKey , 'LastDiscussionID', $body['Discussion']['DiscussionID']);
            self::updateValidValues($catKey , 'LastCommentID', null);
        }

        return $body['Discussion'];
    }

    protected static function addCategory(array $category) {
        $r = self::$api->post(
            '/vanilla/settings/addcategory.json',
            $category
        );
        if ($r->getStatusCode() != 200) {
            throwException('Failed to create new category: ' . json_encode($category));
        }
        $body = $r->getBody();
        return $body['Category'];
    }

    protected static function addComment(array $comment, string $catKey) {
        $r = self::$api->post(
            '/post/comment.json',
            $comment
        );
        if ($r->getStatusCode() != 200) {
            throwException('Failed to add new comment: ' . json_encode($comment));
        }
        $body = $r->getBody();
        if (!empty($catKey)) {
            self::updateValidValues($catKey, 'CountComments', '++', false);
            self::updateValidValues($catKey, 'CountAllComments', '++');
            self::updateValidValues($catKey, 'LastDateInserted', $body['Comment']['DateInserted']);
            self::updateValidValues($catKey , 'LastDiscussionID', $body['Comment']['DiscussionID']);
            self::updateValidValues($catKey , 'LastCommentID', $body['Comment']['CommentID']);
        }
        return $body['Comment'];
    }

    protected static function getCategory(int $categoryId) {
        $r = self::$api->get(
            '/vanilla/settings/getcategory.json',
            ['CategoryID'=>$categoryId]
        );
        if ($r->getStatusCode() != 200) {
            throwException('Failed to get category: ' . $categoryId);
        }
        $body = $r->getBody();
        return $body['Category'];
    }

    /**
     * @depends testModerationDiscussionMoveInintDB
     */
    public function testCategories() {
        foreach (self::$categories as $catKey => $category) {
            $cat = $this->getCategory($category['CategoryID']);
            $this->assertEquals($category['CountAllDiscussions'], $cat['CountAllDiscussions'], 'CountAllDiscussions failed on  '.$catKey);
            $this->assertEquals($category['CountAllComments'], $cat['CountAllComments'], 'CountAllComments failed on  '.$catKey);
            $this->assertEquals($category['CountCategories'], $cat['CountCategories'], 'CountCategories failed on  '.$catKey);
            $this->assertEquals($category['CountDiscussions'], $cat['CountDiscussions'], 'CountDiscussions failed on  '.$catKey.' '.json_encode($cat));
            $this->assertEquals($category['CountComments'], $cat['CountComments'], 'CountComments failed on  '.$catKey);
            $this->assertEquals($category['DateInserted'], $cat['DateInserted'], 'DateInserted failed on  '.$catKey);
            $this->assertEquals($category['DateUpdated'], $cat['DateUpdated'], 'DateUpdated failed on  '.$catKey);
            $this->assertEquals($category['LastDateInserted'], $cat['LastDateInserted'], 'LastDateInserted failed on  '.$catKey);
            $this->assertEquals($category['LastDiscussionID'], $cat['LastDiscussionID'], 'LastDiscussionID failed on  '.$catKey);
            $this->assertEquals($category['LastCommentID'], $cat['LastCommentID'], 'LastCommentID failed on  '.$catKey);
        }

    }

    /**
     * Use case #1:
     * Src cat Lvl6 (cat2_2_1_1_1_1) has 1 discussion with 1 comment
     * Dest cat Lvl3 (cat1_2_1) has 0 discussions
     *
     * @depends testCategories
     */
    public function testConfirmDiscussionMoves() {
        $discussion = self::$discussions['d1_c2_2_1_1_1_1'];
        $category = self::$categories['cat1_2_1'];
        $r = $this->api()->post('/moderation/confirmdiscussionmoves.json?discussionid='.$discussion['DiscussionID'], [
            'Move' => 'Move',
            'CategoryID' => $category['CategoryID']
        ]);

        $body = $r->getBody();
        $status = $r->getStatusCode();

        $this->assertEquals('200' , $status);
        $this->assertArrayHasKey('isHomepage' , $body);
        $this->updateValidValuesOnMoveDiscussion($discussion, 'cat2_2_1_1_1_1', 'cat1_2_1');
        $this->testCategories();
    }


    /**
     * Test adding an admin user.
     *
     * @large
     */
    public function addAdminUser() {
        $system = $this->api()->querySystemUser(true);
        $this->api()->setUser($system);

        $adminUser = [
            'Name' => 'Admin',
            'Email' => 'admin@example.com',
            'Password' => 'adminsecure',
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
}
