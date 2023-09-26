<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Web;

use Garden\Web\Exception\ForbiddenException;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Test for the ApiExtensionMiddleware class.
 */
class ApiExtensionMiddlewareTest extends \VanillaTests\SiteTestCase
{
    use UsersAndRolesApiTestTrait;

    /**
     * Test that only a user with the "exports.manage" permission can get a csv from the api.
     *
     * @return void
     */
    public function testCsvExport(): void
    {
        // An admin user has the "exports.manage" permission.
        $responseWithAdminUser = $this->api()->get("/discussions.csv");
        $this->assertSame(200, $responseWithAdminUser->getStatusCode());

        // A regular old member user doesn't have the permission.
        $memberUser = $this->createUser();
        $this->api()->setUserID($memberUser["userID"]);
        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage("Permission Problem");
        $this->api()->get("/discussions.csv");
    }
}
