<?php
/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Models;

use Gdn_Session;
use SpamModel;
use VanillaTests\BootstrapTrait;
use VanillaTests\EventSpyTestTrait;
use VanillaTests\SetupTraitsTrait;
use VanillaTests\VanillaTestCase;

/**
 * Test {@link SpamModel}.
 */
class SpamModelTest extends VanillaTestCase
{
    use EventSpyTestTrait, BootstrapTrait, SetupTraitsTrait;

    /**
     * Setup
     */
    public function setup(): void
    {
        parent::setUp();
        $this->setUpTestTraits();
    }

    /**
     * Verify tripping the CheckSpam event when posting with Garden.Moderation.Manage = false.
     *
     * @return void
     */
    public function testTrippingCheckSpam()
    {
        /** @var GDN_Session $sessObj */
        $sessObj = $this->container()->get(GDN_Session::class);
        $sessObj->getPermissions()->set("Garden.Moderation.Manage", false);

        SpamModel::isSpam("Comment", []);
        $this->assertEventFired("SpamModel_CheckSpam");
    }

    /**
     * Verify avoiding the CheckSpam event when posting with Garden.Moderation.Manage = true.
     *
     * @return void
     */
    public function testNotTrippingCheckSpam()
    {
        /** @var GDN_Session $sessObj */
        $sessObj = $this->container()->get(GDN_Session::class);
        $sessObj->getPermissions()->set("Garden.Moderation.Manage", true);

        SpamModel::isSpam("Comment", []);
        $this->assertEventNotFired("SpamModel_CheckSpam");
    }
}
