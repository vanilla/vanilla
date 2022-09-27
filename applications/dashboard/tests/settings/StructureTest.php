<?php
/**
 * @author Olivier Lamy-Canuel <olamy-canuel@higherlogic.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\Dashboard\Settings;

use VanillaTests\DatabaseTestTrait;
use VanillaTests\SiteTestCase;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Tests for utility/update.
 */
class StructureTest extends SiteTestCase
{
    use UsersAndRolesApiTestTrait;
    use DatabaseTestTrait;

    /**
     * Test that legacy IPs are properly migrated.
     */
    public function testAllIPsMigration()
    {
        $database = $this->getDb();
        $database
            ->structure()
            ->table("User")
            ->column("AllIPAddresses", "varchar(100)", null)
            ->set();
        $user = $this->createUser();
        $this->userModel->update(["AllIPAddresses" => "127.0.0.1"], ["UserID" => $user["userID"]]);

        $response = $this->bessy()->postJsonData("/utility/update");
        $this->assertEquals(200, $response->getStatus());

        $result = $database
            ->createSql()
            ->getWhere("User", ["UserID" => $user["userID"]])
            ->resultArray();
        $this->assertNull($result[0]["AllIPAddresses"]);
        $this->assertNotNull($result[0]["UpdateIPAddress"]);
    }
}
