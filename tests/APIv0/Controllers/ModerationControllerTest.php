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

    protected static $categories = [];

    protected static $discussions = [];

    protected static $validResponses = [
        'CountAllDiscussions' => [
            'cat1' => 2,
            'cat1_1' => 2,
            'cat1_1_1' => 2,
            'cat1_2' => 0,
            'cat1_2_1' => 0,
            'cat2' => 2,
            'cat2_1' => 0,
            'cat2_1_1' => 0,
            'cat2_2' => 2,
            'cat2_2_1' => 2,
            'cat2_2_1_1' => 2,
            'cat2_2_1_1_1' => 2,
            'cat2_2_1_1_1_1' => 2,
        ],
        'CountAllComments' => [
            'cat1' => 1,
            'cat1_1' => 1,
            'cat1_1_1' => 1,
            'cat1_2' => 0,
            'cat1_2_1' => 0,
            'cat2' => 1,
            'cat2_1' => 0,
            'cat2_1_1' => 0,
            'cat2_2' => 1,
            'cat2_2_1' => 1,
            'cat2_2_1_1' => 1,
            'cat2_2_1_1_1' => 1,
            'cat2_2_1_1_1_1' => 1,

        ],
        'CountCategories' => [
            'cat1' => 0,
            'cat1_1' => 0,
            'cat1_1_1' => 0,
            'cat1_2' => 0,
            'cat1_2_1' => 0,
            'cat2' => 0,
            'cat2_1' => 0,
            'cat2_1_1' => 0,
            'cat2_2' => 0,
            'cat2_2_1' => 0,
            'cat2_2_1_1' => 0,
            'cat2_2_1_1_1' => 0,
            'cat2_2_1_1_1_1' => 0,

        ],
        'CountDiscussions' => [
            'cat1' => 0,
            'cat1_1' => 0,
            'cat1_1_1' => 2,
            'cat1_2' => 0,
            'cat1_2_1' => 0,
            'cat2' => 0,
            'cat2_1' => 0,
            'cat2_1_1' => 0,
            'cat2_2' => 0,
            'cat2_2_1' => 0,
            'cat2_2_1_1' => 0,
            'cat2_2_1_1_1' => 0,
            'cat2_2_1_1_1_1' => 2,

        ],
        'CountComments' => [
            'cat1' => 0,
            'cat1_1' => 0,
            'cat1_1_1' => 1,
            'cat1_2' => 0,
            'cat1_2_1' => 0,
            'cat2' => 0,
            'cat2_1' => 0,
            'cat2_1_1' => 0,
            'cat2_2' => 0,
            'cat2_2_1' => 0,
            'cat2_2_1_1' => 0,
            'cat2_2_1_1_1' => 0,
            'cat2_2_1_1_1_1' => 1,

        ],
        'DateInserted' => [],
        'DateUpdated' => [],

    ];

    public static function setUpBeforeClass() {
        parent::setUpBeforeClass();
    }

    public function testModerationDiscussionMoveInintDB() {
        $this->api()->saveToConfig([
            'Garden.Registration.Method' => 'Basic',
            'Garden.Registration.ConfirmEmail' => false,
            'Garden.Registration.SkipCaptcha' => true,
            'Vanilla.Discussions.Add'=>true,
            'Vanilla.Discussions.Edit'=>true,
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
            'Name' => 'Discussion 1 of cat-1-1-1',
            'Body' => 'Test '.rand(1,9999999999)
        ]);

        $comment = $this->addComment([
            'DiscussionID' => $discussion['DiscussionID'],
            'Body' => 'Moderation controller test. LINE: '.__LINE__.' DATE: '.date('r')
        ]);

        self::$discussions['d2_c1-1-1'] = $discussion = $this->addDiscussion([
            'CategoryID' => $cat1_1_1['CategoryID'],
            'Name' => 'Discussion 2 of cat-1-1-1',
            'Body' => 'Test '.rand(1,9999999999)
        ]);

        $this->updateValidValues('cat1_1_1' , 'LastDateInserted', $discussion['DateInserted']);

        self::$discussions['d1_c2_2_1_1_1_1'] = $discussion = $this->addDiscussion([
            'CategoryID' => $cat2_2_1_1_1_1['CategoryID'],
            'Name' => 'Discussion 1 of cat2_2_1_1_1_1',
            'Body' => 'Test '.rand(1,9999999999)
        ]);

        self::$discussions['d2_c2_2_1_1_1_1'] = $discussion = $this->addDiscussion([
            'CategoryID' => $cat2_2_1_1_1_1['CategoryID'],
            'Name' => 'Discussion 2 of cat2_2_1_1_1_1',
            'Body' => 'Test '.rand(1,9999999999)
        ]);

        $comment = $this->addComment([
            'DiscussionID' => $discussion['DiscussionID'],
            'Body' => 'Moderation controller test. LINE: '.__LINE__.' DATE: '.date('r')
        ]);

        $this->updateValidValues('cat2_2_1_1_1_1' , 'LastDateInserted', $comment['DateInserted']);

        $this->assertTrue( true);
    }

    protected function updateValidValues(string $catKey, string $fieldToUpdate, $newValue, bool $recursively = true) {
        do {
            $continue = false;
            switch ($newValue) {
                case '++':
                    self::$validResponses[$fieldToUpdate][$catKey]++;
                    break;
                case '--':
                    self::$validResponses[$fieldToUpdate][$catKey]--;
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

    protected static function addDiscussion(array $discussion) {
        $r = self::$api->post(
            '/post/discussion.json',
            $discussion
        );
        if ($r->getStatusCode() != 200) {
            throwException('Failed to create new discussion: ' . json_encode($discussion));
        }
        $body = $r->getBody();
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

    protected static function addComment(array $comment) {
        $r = self::$api->post(
            '/post/comment.json',
            $comment
        );
        if ($r->getStatusCode() != 200) {
            throwException('Failed to add new comment: ' . json_encode($comment));
        }
        $body = $r->getBody();
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
            $this->assertEquals(self::$validResponses['CountAllDiscussions'][$catKey], $cat['CountAllDiscussions'], 'CountAllDiscussions failed on  '.$catKey);
            $this->assertEquals(self::$validResponses['CountAllComments'][$catKey], $cat['CountAllComments'], 'CountAllComments failed on  '.$catKey);
            $this->assertEquals(self::$validResponses['CountCategories'][$catKey], $cat['CountCategories'], 'CountCategories failed on  '.$catKey);
            $this->assertEquals(self::$validResponses['CountDiscussions'][$catKey], $cat['CountDiscussions'], 'CountDiscussions failed on  '.$catKey.' '.json_encode($cat));
            $this->assertEquals(self::$validResponses['CountComments'][$catKey], $cat['CountComments'], 'CountComments failed on  '.$catKey);
            $this->assertEquals($category['DateInserted'], $cat['DateInserted'], 'DateInserted failed on  '.$catKey);
            $this->assertEquals($category['DateUpdated'], $cat['DateUpdated'], 'DateUpdated failed on  '.$catKey);
            $this->assertEquals($category['LastDateInserted'], $cat['LastDateInserted'], 'LastDateInserted failed on  '.$catKey);

        }

    }

    /**
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


        $this->updateValidValues('cat1_2_1' , 'CountAllDiscussions', '++');
        // Right now CountDiscussions field is not updated at all  - which is wrong
        // @todo We need to uncomment next 2 lines when bug is fixed
        // self::$validResponses['CountDiscussions']['cat1_2_1']++;
        // self::$validResponses['CountDiscussions']['cat2_2_1_1_1_1']--;
        $this->updateValidValues('cat2_2_1_1_1_1' , 'CountAllDiscussions', '--');

        $this->updateValidValues('cat1_2_1' , 'LastDateInserted', $discussion['DateInserted']);

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
