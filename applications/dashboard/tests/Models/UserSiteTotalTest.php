<?php
/**
 * @author Olivier Lamy-Canuel <olamy-canuel@higherlogic.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

use VanillaTests\SiteTestCase;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Test the user counts of the site-total endpoint.
 */
class UserSiteTotalTest extends SiteTestCase
{
    use UsersAndRolesApiTestTrait;

    protected $baseURL = "/site-totals";
    protected $query = ["counts[]" => "user"];

    /**
     * Test user counts from /site-totals.
     */
    public function testUserCount()
    {
        // includes 5 base users from Vanilla + circleCI user.
        $result = $this->api->get($this->baseURL, $this->query)->getBody();
        $this->assertEquals(6, $result["counts"]["user"]["count"]);

        self::$testCache->flush();
        $user = $this->createUser();
        $result = $this->api->get($this->baseURL, $this->query)->getBody();
        $this->assertEquals(7, $result["counts"]["user"]["count"]);

        // Verify that deleted user are excluded.
        self::$testCache->flush();
        $this->userModel->deleteID($user["userID"]);
        $result = $this->api->get($this->baseURL, $this->query)->getBody();
        $this->assertEquals(6, $result["counts"]["user"]["count"]);
    }
}
