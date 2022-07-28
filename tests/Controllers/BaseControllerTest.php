<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Controllers;

use VanillaTests\SiteTestCase;

/**
 * Some tests for base Gdn_Controller functionality.
 */
class BaseControllerTest extends SiteTestCase
{
    public static $addons = ["test"];

    /**
     * {@inheritDoc}
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->createUserFixtures();
        $this->enableCaching();

        $this->getSession()->start($this->adminID);
        $this->bessy()->setRethrowExceptions(true);
    }

    /**
     * Test a basic permission denial.
     */
    public function testNoPermission(): void
    {
        $this->getSession()->end();
        try {
            $r = $this->bessy()->get("/test/no-guest");
            $this->fail("There should have been an exception.");
        } catch (\Gdn_UserException $ex) {
            $this->assertStringContainsString("You don't have permission to do that.", $ex->getMessage());
            $this->assertLog(["event" => "security_denied"]);
        }
    }

    /**
     * Accessing an admin page should log the access.
     */
    public function testAdminAccessLogged(): void
    {
        $r = $this->bessy()->get("/test/admin");
        $this->assertLog(["event" => "security_access"]);
    }

    /**
     * A permission exception thrown in a controller should also log a security_denied event.
     */
    public function testLogPermissionErrorOnDispatch(): void
    {
        $this->bessy()->setRethrowExceptions(false);

        $r = $this->bessy()->get("/test/permission-exception");
        $this->assertStringContainsString("You need to be an administrator to do that.", $r->data("Message"));
        $log = $this->assertLog(["event" => "security_denied"]);
    }
}
