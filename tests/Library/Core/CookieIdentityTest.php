<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core;

use Gdn;
use Gdn_CookieIdentity;
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
}
