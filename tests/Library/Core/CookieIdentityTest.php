<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core;

use Firebase\JWT\JWT;
use Garden\Web\Exception\ForbiddenException;
use Gdn;
use Gdn_CookieIdentity;
use Vanilla\CurrentTimeStamp;
use VanillaTests\APIv2\AbstractAPIv2Test;

/**
 * Integration tests for `Gdn_DirtyCache`.
 */
class CookieIdentityTest extends AbstractAPIv2Test
{
    /** @var Gdn_CookieIdentity */
    public $cookieIdentity;

    /**
     * @inheritDoc
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->createUserFixtures();
        $this->cookieIdentity = Gdn::factory("Identity");
        $this->cookieIdentity->init();
    }

    /**
     * @inheritDoc
     */
    public function testGetIdentity()
    {
        $origSessionID = betterRandomString(12);
        $this->cookieIdentity->setIdentity($this->memberID, true, $origSessionID);
        $sessionID = $this->cookieIdentity->getSession();
        $this->assertEquals($sessionID, $origSessionID);
    }

    /**
     * Checking Exception if session ID is not in the cookie.
     */
    public function testGetIdentityException()
    {
        $this->cookieIdentity->setIdentity($this->memberID, true);
        $this->cookieIdentity->UserID = 0;
        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage("Cookie must have session ID.");
        $this->cookieIdentity->getSession();
    }

    /**
     * Tests fallback to OldSalt for CookieIdentity::getJWTPayload
     *
     * @return void
     * @throws \Exception
     */
    public function testGetJWTPayload()
    {
        $this->cookieIdentity->init(["Salt" => "1234567890123456"]);
        $_COOKIE["blah"] = JWT::encode(["foo" => "bar"], "1234567890123456", Gdn_CookieIdentity::JWT_ALGORITHM);

        // Basic test
        $payload = $this->cookieIdentity->getJWTPayload("blah");
        $this->assertSame(["foo" => "bar"], $payload);

        // Test if salt changes
        $this->cookieIdentity->init(["Salt" => "12345678901234567890123456789012"]);
        $payload = $this->cookieIdentity->getJWTPayload("blah");
        $this->assertSame(null, $payload);

        // Test with OldSalt fallback
        $this->runWithConfig(["Garden.Cookie.OldSalt" => "1234567890123456"], function () {
            $payload = $this->cookieIdentity->getJWTPayload("blah");
            $this->assertSame(["foo" => "bar"], $payload);
        });
    }
}
