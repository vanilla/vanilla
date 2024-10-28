<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

use Vanilla\CurrentTimeStamp;
use Vanilla\Web\SystemTokenUtils;
use VanillaTests\SiteTestCase;
use VanillaTests\TestLoggerTrait;

/**
 * Tests for service based system tokens.
 */
class ServiceSystemTokenTest extends SiteTestCase
{
    use TestLoggerTrait;

    private function systemTokenUtils(): SystemTokenUtils
    {
        return self::container()->get(SystemTokenUtils::class);
    }

    /**
     * Test the happy path.
     *
     * @return void
     */
    public function testValidToken(): void
    {
        $token = "vnla_sys." . $this->systemTokenUtils()->encode(service: "Vanilla Queue");
        $this->authenticateSystemToken($token);
        $this->assertEquals(\Gdn::userModel()->getSystemUserID(), \Gdn::session()->UserID);
        $this->assertLogMessage("Service \"Vanilla Queue\" made a request as system.");
    }

    /**
     * Test error if no service is specified.
     */
    public function testNoServiceSpecified(): void
    {
        $token = "vnla_sys." . $this->systemTokenUtils()->encode();
        $this->expectExceptionMessage("Dynamic system token must declare a service.");
        $this->authenticateSystemToken($token);
    }

    /**
     * Test error if there is an invalid token.
     */
    public function testInvalidToken(): void
    {
        $token = "vnla_sys.asdfasdf";
        $this->expectExceptionMessage("Invalid system token - Wrong number of segments");
        $this->authenticateSystemToken($token);
    }

    /**
     * Test error if the token is expired.
     */
    public function testExpiredToken(): void
    {
        CurrentTimeStamp::mockTime("2024-01-01");
        $token = "vnla_sys." . $this->systemTokenUtils()->encode(service: "Vanilla Queue");
        CurrentTimeStamp::mockTime("2024-01-03");
        $this->expectExceptionMessage("Invalid system token - Expired token");
        $this->authenticateSystemToken($token);
    }

    /**
     * Utility to try and authenticate with a system token.
     *
     * @param string $token
     * @return void
     */
    private function authenticateSystemToken(string $token): void
    {
        $request = \Gdn::request();
        $request->setPath("/api/v2/test")->setHeader("Authorization", "Bearer $token");
        $this->api()->setUserID(\UserModel::GUEST_USER_ID);
        self::container()
            ->get(\DashboardHooks::class)
            ->gdn_auth_startAuthenticator_handler();
    }
}
