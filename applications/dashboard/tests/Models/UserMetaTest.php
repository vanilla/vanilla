<?php
/**
 * @author Olivier Lamy-Canuel <olamy-canuel@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Models;

use UserMetaModel;
use VanillaTests\SiteTestCase;
use VanillaTests\UsersAndRolesApiTestTrait;

class UserMetaTest extends SiteTestCase
{
    use UsersAndRolesApiTestTrait;

    /**
     * {@inheritDoc}
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->userMetaModel = $this->container()->get(UserMetaModel::class);
    }

    /**
     * Test that UserMeta values inserted from both the UserModel and UserMetaModel are propely inserted into the database.
     *
     * @param $text
     * @param $expected
     * @dataProvider provideUserMetaData
     */
    public function testUserMetaInsert($text, $expected)
    {
        $user = $this->createUser();
        $this->userModel->setMeta($user["userID"], [__FUNCTION__ . "userModel" => $text]);
        $result = $this->userMetaModel->getUserMeta($user["userID"], __FUNCTION__ . "userModel");
        $this->assertEquals($expected, $result[__FUNCTION__ . "userModel"]);

        $this->userMetaModel->setUserMeta($user["userID"], __FUNCTION__ . "userMetaModel", $text);
        $result = $this->userMetaModel->getUserMeta($user["userID"], __FUNCTION__ . "userMetaModel");
        $this->assertEquals($expected, $result[__FUNCTION__ . "userMetaModel"]);
    }

    /**
     * DataProvider for testUserMetaInsert.
     *
     * @return array
     */
    public function provideUserMetaData()
    {
        $r = [
            ["thisIsATest", "thisIsATest"],
            ["this\u{0009}Is\u{3000}A\u{2029}Test", "thisIsATest"],
            [" thisIsATest ", "thisIsATest"],
            ["En français", "En français"],
            ["ช่องโปรไฟล์", "ช่องโปรไฟล์"],
        ];
        return $r;
    }
}
