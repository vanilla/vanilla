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


    public function testModerationDiscussionMoveInintDB() {
        $this->api()->saveToConfig([
            'Garden.Registration.Method' => 'Basic',
            'Garden.Registration.ConfirmEmail' => false,
            'Garden.Registration.SkipCaptcha' => true,
            //'Cache.Enabled' => false,
        ]);

        self::$testUser = $this->addAdminUser();
        $this->api()->setUser(self::$testUser);


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
        self::$discussions[$discussion['discussionKey']]['CategoryID'] = self::$categories[$destCategoryKey]['CategoryID'];
        // Right now CountDiscussions field is not updated at all  - which is wrong
        // @todo We need to uncomment next 2 lines when bug is fixed
        // self::updateValidValues($destCategoryKey,'CountDiscussions','++');
        // self::updateValidValues($srcCategoryKey,'CountDiscussions','--');


        self::updateValidValues($destCategoryKey , 'CountAllDiscussions', '++');
        self::updateValidValues($srcCategoryKey , 'CountAllDiscussions', '--');
        if ($updateRecent) {
            if ($discussion['DateInserted'] > self::$categories[$destCategoryKey]['LastDateInserted']) {
                self::updateValidValues($destCategoryKey , 'LastDateInserted', $discussion['DateInserted']);
                self::updateValidValues($destCategoryKey , 'LastDiscussionID', $discussion['DiscussionID']);
            } else {
                //echo $destCategoryKey.':'.$discussion['DateInserted'].':'.self::$categories[$destCategoryKey]['LastDateInserted']."\n";
                self::updateValidValues($destCategoryKey , 'LastDateInserted', self::$categories[$destCategoryKey]['LastDateInserted']);
                self::updateValidValues($destCategoryKey , 'LastDiscussionID', self::$categories[$destCategoryKey]['LastDiscussionID']);
            }

            // Right now LastDateInserted and LastDiscussionID fields are not updated against source Category - which is wrong
            // @todo We need to uncomment next 2 lines when bug is fixed
            //self::updateValidValues($srcCategoryKey , 'LastDateInserted', ($srcDiscussion['DateInserted'] ?? null));
            //self::updateValidValues($srcCategoryKey , 'LastDiscussionID', ($srcDiscussion['DiscussionID'] ?? null));
            // @todo until then lets update in non-recursive mode to reproduce current data flow
            self::updateValidValues($srcCategoryKey , 'LastDateInserted', ($srcDiscussionToUpdate['DateInserted'] ?? null), false);
            self::updateValidValues($srcCategoryKey , 'LastDiscussionID', ($srcDiscussionToUpdate['DiscussionID'] ?? null), false);
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
    protected function moveDiscussion(array $discussion, string $srcCatKey, string $destCatKey, array $srcDiscussion = []) {
        $r = self::$api->post('/moderation/confirmdiscussionmoves.json?discussionid='.$discussion['DiscussionID'], [
        'Move' => 'Move',
        'CategoryID' => self::$categories[$destCatKey]['CategoryID']
        ]);

        $this->updateValidValuesOnMoveDiscussion($discussion, $srcCatKey, $destCatKey, true , $srcDiscussion);
    }

    protected static function addCategory(array $category, string $catKey = '') {
        if (!empty($catKey)) {
            if (($pos = strrpos($catKey, '_')) > 0) {
                $catKey = substr($catKey, 0, $pos);
                if (!($parentCat = (self::$categories[$catKey] ?? false))) {
                    self::$categories[$catKey] = $parentCat = self::addCategory(['Name' => ' Test cat '.$catKey,
                        'UrlCode' => 'test-cat-'.$catKey,
                        'DisplayAs' => 'Discussions'], $catKey);
                }
                $category['ParentCategoryID'] = $parentCat['CategoryID'];
            }
        }

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
            '/vanilla/settings/getcategory.json?'.rand(0,1000),
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
    public function recheckCategories() {
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
     * Src cat Lvl1-6 has 1 discussion
     * Dest cat Lvl1-6 has 0 discussions
     *
     * @depends testModerationDiscussionMoveInintDB
     */
    public function testCase1() {
        $this->createAndMove('cat1', 'cat2', 'd1_case1');
        $this->createAndMove('cat1_1_1', 'cat2_1_1', 'd1_case2');
        $this->createAndMove('cat1_1_1_1', 'cat2_1_1_1', 'd1_case3');
        $this->createAndMove('cat1_1_1_1_1', 'cat2_1_1_1_1', 'd1_case4');
        $this->createAndMove('cat1_1_1_1_1_1', 'cat2_1_1_1_1_1', 'd1_case5');
        $this->createAndMove('cat1_1_1_1_1_1_1', 'cat2_1_1_1_1_1_1', 'd1_case6');
        $this->recheckCategories();
    }

    /**
     * Use case #2:
     * Src cat Lvl1-6 has 1 discussion
     * Dest cat cat3_1_1 has 0-1-2-3 discussions
     *
     * @depends testCase1
     */
    public function testCase2() {
        $destCatKey = 'cat3_1_1';
        self::$categories[$destCatKey] = $destCategory = self::addCategory([
            'Name' => 'Test cat '.$destCatKey,
            'UrlCode' => 'test-cat-'.$destCatKey,
            'DisplayAs' => 'Discussions'], $destCatKey);
        $this->moveDiscussion(self::$discussions['d1_case1'], 'cat2', $destCatKey, self::$discussions['d1_case6']);
        $this->moveDiscussion(self::$discussions['d1_case2'], 'cat2_1_1', $destCatKey, self::$discussions['d1_case6']);
        $this->moveDiscussion(self::$discussions['d1_case3'], 'cat2_1_1_1', $destCatKey, self::$discussions['d1_case6']);
        $this->moveDiscussion(self::$discussions['d1_case4'], 'cat2_1_1_1_1', $destCatKey, self::$discussions['d1_case6']);
        $this->recheckCategories();
    }

    /**
     * Use case #3:
     * Src cat Lvl1-6
     * Dest cat cat2_1 has 0-1-2-3 discussions
     * but has LastDiscussionID fresher than we move in.
     *
     * @depends testCase2
     */
    public function testCase3() {
        $destCatKey = 'cat2_1';
        $this->moveDiscussion(self::$discussions['d1_case1'], 'cat3_1_1', $destCatKey, self::$discussions['d1_case4']);
        $this->moveDiscussion(self::$discussions['d1_case2'], 'cat3_1_1', $destCatKey, self::$discussions['d1_case4']);
        $this->recheckCategories();
    }

    public function createAndMove(string $srcCatKey, string $destCatKey, string $discussionKey ) {
        if ( !($srcCategory = (self::$categories[$srcCatKey] ?? false))) {
            self::$categories[$srcCatKey] = $srcCategory = self::addCategory([
                'Name' => 'Test cat '.$srcCatKey,
                'UrlCode' => 'test-cat-'.$srcCatKey,
                'DisplayAs' => 'Discussions'], $srcCatKey);
        }
        $discussion = self::$discussions[$discussionKey] = self::addDiscussion([
            'Name' => 'Discussion 1 of '.$srcCatKey,
            'Body' => 'Test '.$srcCatKey.' '.$destCatKey,
            'CategoryID' => $srcCategory['CategoryID']
        ],$srcCatKey);
        $discussion['discussionKey'] = self::$discussions[$discussionKey]['discussionKey'] = $discussionKey;
        if ( !($destCategory = (self::$categories[$destCatKey] ?? false))) {
            self::$categories[$destCatKey] = $destCategory = self::addCategory([
                'Name' => ' Test cat ' . $destCatKey,
                'UrlCode' => 'test-cat-' . $destCatKey,
                'DisplayAs' => 'Discussions'
            ], $destCatKey);
        }
        $this->moveDiscussion($discussion, $srcCatKey, $destCatKey);
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
