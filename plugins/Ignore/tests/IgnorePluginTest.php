<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

/**
 * Tests for the Ignore plugin.
 */
class IgnorePluginTest extends \VanillaTests\SiteTestCase
{
    use \VanillaTests\UsersAndRolesApiTestTrait;
    use \VanillaTests\Forum\Utils\CommunityApiTestTrait;
    use \VanillaTests\EventSpyTestTrait;

    public static $addons = ["vanilla", "ignore"];

    /** @var IgnorePlugin */
    private $ignorePlugin;

    /**
     * {@inheritDoc}
     */
    public function setUp(): void
    {
        $this->ignorePlugin = Gdn::getContainer()->get(IgnorePlugin::class);
        parent::setUp();
    }

    /**
     * Test that we aren't sending notifications when a user is @-mentioned by a user they're ignoring.
     */
    public function testIgnoreAtMentions(): void
    {
        $userA = $this->createUser();
        $userB = $this->createUser();

        $session = $this->getSession();
        $this->api()->setUserID($userB["userID"]);
        $this->cleanupEventSpyTestTrait();
        $this->createDiscussion(["body" => "Definitely notify @{$userA["name"]} about this"]);

        // A notification should be sent when a user is @-mentioned.
        $this->assertEventDispatched(
            $this->expectedResourceEvent("notification", \Garden\Events\ResourceEvent::ACTION_INSERT, []),
            []
        );
        $session->start($userA["userID"]);
        $this->bessy()->post("user/ignore/toggle/{$userB["userID"]}/{$userB["name"]}");

        // Confirm userA is ignoring userB
        $aIgnoringB = $this->ignorePlugin->ignored($userB["userID"]);
        $this->assertTrue($aIgnoringB);

        $this->api()->setUserID($userB["userID"]);
        $this->cleanupEventSpyTestTrait();
        $this->createDiscussion(["body" => "Don't notify @{$userA["name"]} about this"]);
        // Since the user doing the @-mentioning is being ignored, no notification should be sent.
        $this->assertEventNotDispatched([
            "type" => "notification",
            "action" => \Garden\Events\ResourceEvent::ACTION_INSERT,
        ]);
    }
}
