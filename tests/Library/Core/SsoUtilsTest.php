<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core;

use Garden\Web\Cookie;
use PHPUnit\Framework\TestCase;
use VanillaTests\Bootstrap;
use VanillaTests\SiteTestTrait;

/**
 * Tests for the `SsoUtils` class.
 */
class SsoUtilsTest extends TestCase {
    use SiteTestTrait;

    /**
     * @var Cookie
     */
    private $cookie;

    /**
     * @var \SsoUtils
     */
    private $ssoUtils;

    /**
     * @inheritDoc
     */
    public function setUp(): void {
        parent::setUp();
        $this->setupSiteTestTrait();
        $this->container()->call(function (
            \SsoUtils $ssoUtils,
            Cookie $cookie
        ) {
            $this->ssoUtils = $ssoUtils;
            $this->cookie = $cookie;
        });
    }

    /**
     * Test creating and then verifying a state token.
     */
    public function testStateToken(): void {
        $token = $this->ssoUtils->getStateToken(true);
        $this->ssoUtils->verifyStateToken('test', $token);
        $this->assertTrue(true);
    }

    /**
     * An empty state token should not verify.
     */
    public function testEmptyStateToken(): void {
        $this->expectExceptionCode(403);
        $this->ssoUtils->verifyStateToken('test', '');
    }

    /**
     * An invalid state token should not verify.
     */
    public function testInvalidStateToken(): void {
        $this->expectExceptionCode(400);
        $this->ssoUtils->verifyStateToken('test', 'foo');
    }

    /**
     * Test a valid token that hasn't been saved.
     */
    public function testDifferentToken(): void {
        $token1 = $this->ssoUtils->getStateToken(true);
        $token2 = $this->ssoUtils->getStateToken(true);
        $this->expectExceptionCode(400);
        $this->ssoUtils->verifyStateToken('test', $token1);
    }
}
