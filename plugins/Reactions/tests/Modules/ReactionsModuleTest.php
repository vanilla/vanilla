<?php
/**
 * @author Richard Flynn <richard.flynn@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Modules;

use Gdn;
use ReactionsModule;
use VanillaTests\SiteTestCase;

/**
 * Class ReactionsModuleTest
 * @package VanillaTests\Modules
 */
class ReactionsModuleTest extends SiteTestCase
{
    public static $addons = ["vanilla", "reactions"];

    /**
     * @inheritdoc
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->createUserFixtures();
    }

    /**
     * Tests that the correct user is set for the ReactionsModule's user value.
     *
     * @throws \Exception Exception.
     */
    public function testCorrectUser()
    {
        // The module's user should be the profile being viewed. In this case, that will be the member.
        $memberID = $this->createUserFixture(self::ROLE_MEMBER);
        $member = $this->api()
            ->get("users/{$memberID}")
            ->getBody();
        $memberName = $member["name"];
        $memberProfile = $this->bessy()->get("/profile/{$memberName}");
        // Set the reactions module
        $reactionsModule = Gdn::getContainer()->get(ReactionsModule::class);
        $memberProfile->addModule($reactionsModule, "Content");
        // Calling toString() will set the user on the reactions module.
        $reactionsModule->toString();
        $memberReactionsModuleUser = $reactionsModule->user ?? "foo";
        $memberUser = $memberProfile->User ?? "bar";

        $this->assertEquals($memberReactionsModuleUser, $memberUser);

        // In this case, the module's user should be the admin.
        $adminProfile = $this->bessy()->get("/profile");
        $secondReactionsModule = Gdn::getContainer()->get(ReactionsModule::class);
        $adminProfile->addModule($secondReactionsModule, "Content");
        $secondReactionsModule->toString();
        $adminReactionsModuleUser = $secondReactionsModule->user ?? "foo";
        $adminUser = $adminProfile->User ?? "bar";
        $this->assertEquals($adminReactionsModuleUser, $adminUser);
    }
}
