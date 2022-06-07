<?php
/**
 * @author Pavel Goncharov <pgoncharov@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Models;

use Gdn;
use LogModel;
use VanillaTests\SiteTestCase;
use \UserModel;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Test {@link UserModel}.
 */
class LogModelTest extends SiteTestCase
{
    use UsersAndRolesApiTestTrait;

    /**
     * @var \Gdn_Session
     */
    private $session;

    /**
     * Get a new model for each test.
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->session = Gdn::session();
    }

    /**
     * Test createLogPostEvent
     *
     */
    public function testCreateLogPostEvent()
    {
        $logPostEvent = LogModel::createLogPostEvent(
            "save",
            "registration",
            ["test" => "result"],
            "reactions",
            $this->session->UserID,
            "negative",
            null
        );

        $this->assertSame("save", $logPostEvent->getAction());
    }
}
