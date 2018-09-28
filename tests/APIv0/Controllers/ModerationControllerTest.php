<?php
/**
 * Created by PhpStorm.
 * User: alexander
 * Date: 2018-09-27
 * Time: 11:31 AM
 */

namespace VanillaTests\APIv0\Controllers;

use VanillaTests\APIv0\BaseTest;

class ModerationControllerTest extends BaseTest {
    protected static $testUser;

    protected static $categories = [];

    protected static $discussions = [];

    protected static $validResponses = [
        'CountAllDiscussions' => [
            'cat1' => 2,
            'cat1_1' => 2,
            'cat1_1_1' => 2,
            'cat1_2' => 0,
            'cat1_2_1' => 0,
            'cat2' => 0,
            'cat2_1' => 0,
            'cat2_1_1' => 0,
            'cat2_2' => 0,

        ]
    ];

    public static function setUpBeforeClass() {
        parent::setUpBeforeClass();
    }

    public function testModerationDiscussionMoveInintDB() {
        $this->api()->saveToConfig([
            'Garden.Registration.Method' => 'Basic',
            'Garden.Registration.ConfirmEmail' => false,
            'Garden.Registration.SkipCaptcha' => true,
        ]);

        $testUser = $this->addAdminUser();

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


        $discussion = $this->addDiscussion([
            'CategoryID' => $cat1_1_1['CategoryID'],
            'Name' => 'Discussion 1 of cat-1-1-1',
            'Body' => 'Test '.rand(1,9999999999)
        ]);

        $discussion = $this->addDiscussion([
            'CategoryID' => $cat1_1_1['CategoryID'],
            'Name' => 'Discussion 2 of cat-1-1-1',
            'Body' => 'Test '.rand(1,9999999999)
        ]);
        //throw new \Exception(json_encode($discussion));

        $this->assertTrue( true);
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
    public function testCountAllDiscussions() {
        foreach (self::$categories as $catKey => $category) {
            $cat = $this->getCategory($category['CategoryID']);
            $this->assertEquals(self::$validResponses['CountAllDiscussions'][$catKey], $cat['CountAllDiscussions']);
        }

    }
//        $r = $this->api()->post('/moderation/confirmdiscussionmoves?discussionid=7359', [
//            'Move' => 'Move',
//            'CategpryID' => 1
//        ]);
//
//        $body = $r->getBody();
//        $status = $r->getStatusCode();
//
//        $this->assertEquals('200' , $status);
//        $this->assertEquals(0 , strlen($body));
//    }

//    public function testConfirmDiscussionMoves() {
//        $r = $this->api()->post('/moderation/confirmdiscussionmoves?discussionid=7359', [
//            'Move' => 'Move',
//            'CategpryID' => 1
//        ]);
//
//        $body = $r->getBody();
//        $status = $r->getStatusCode();
//
//        $this->assertEquals('200' , $status);
//        $this->assertEquals(0 , strlen($body));
//    }


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
