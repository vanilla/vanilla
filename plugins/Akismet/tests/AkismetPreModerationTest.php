<?php
/**
 * @author Olivier Lamy-Canuel <olamy-canuel@higherlogic.com>
 * @copyright 2009-2024 Higher Logic Inc.
 * @license Proprietary
 */

use Vanilla\Contracts\ConfigurationInterface;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\SiteTestCase;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Test the Akismet premoderation handler.
 */
class AkismetPreModerationTest extends SiteTestCase
{
    /** This value is hardcoded by Akismet to always return as Spam. */
    const SPAMMER_USERNAME = "akismet‑guaranteed‑spam";

    /** This value is hardcoded by Akismet to always return as Spam. */
    const SPAMMER_EMAIL = "akismet-guaranteed-spam@example.com";

    public static $addons = ["akismet"];

    use CommunityApiTestTrait;
    use UsersAndRolesApiTestTrait;

    /**
     * @inheritdoc
     */
    public function setUp(): void
    {
        parent::setUp();
        $config = $this->container()->get(ConfigurationInterface::class);
        $config->set("Feature.escalations.Enabled", true);

        $apiKey = getenv("AKISMET_KEY");
        if ($apiKey) {
            $config->saveToConfig("Plugins.Akismet.Key", $apiKey);
        } else {
            $mockPlugin = new MockAkismetPlugin();
            $this->container()->setInstance(AkismetPlugin::class, $mockPlugin);
        }
    }

    /**
     * Test that a spam discussion is detected and not added.
     *
     * @return void
     */
    public function testDiscussionIsSpam(): void
    {
        $user = $this->createUser(["name" => self::SPAMMER_USERNAME]);

        $this->runWithUser(function () {
            $this->createDiscussion();
        }, $user);

        $discussions = $this->api()
            ->get("discussions", ["insertUserID" => $user["userID"]])
            ->getBody();
        $this->assertEmpty($discussions);
    }

    /**
     * Test that regular discussion is not detected as spam.
     *
     * @return void
     */
    public function testDiscussionIsNotSpam(): void
    {
        $user = $this->createUser();

        $this->runWithUser(function () {
            $this->createDiscussion();
        }, $user);

        $discussions = $this->api()
            ->get("discussions", ["insertUserID" => $user["userID"]])
            ->getBody();
        $this->assertNotEmpty($discussions);
    }

    /**
     * Test that a spam comment is detected and not added.
     *
     * @return void
     */
    public function testCommentIsSpam(): void
    {
        $this->createDiscussion();
        $user = $this->createUser(["email" => self::SPAMMER_EMAIL]);

        $this->runWithUser(function () {
            $this->createComment();
        }, $user);

        $comments = $this->api()
            ->get("comments", ["insertUserID" => $user["userID"]])
            ->getBody();
        $this->assertEmpty($comments);
    }

    /**
     * Test that regular comment is not detected as spam.
     *
     * @return void
     */
    public function testCommentIsNotSpam(): void
    {
        $this->createDiscussion();
        $user = $this->createUser();

        $this->runWithUser(function () {
            $this->createComment();
        }, $user);

        $comments = $this->api()
            ->get("comments", ["insertUserID" => $user["userID"]])
            ->getBody();

        $this->assertNotEmpty($comments);
    }
}
