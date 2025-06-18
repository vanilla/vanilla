<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\Signatures;

use Vanilla\Formatting\Formats\HtmlFormat;
use Vanilla\Formatting\Formats\RichFormat;
use Vanilla\Formatting\Formats\TextFormat;
use VanillaTests\APIv0\TestDispatcher;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\SiteTestCase;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Tests for signatures.
 */
class SignaturesTest extends SiteTestCase
{
    use CommunityApiTestTrait;
    use UsersAndRolesApiTestTrait;

    public static $addons = ["Signatures", "ranks"];

    /**
     * @inheritdoc
     */
    public function setUp(): void
    {
        parent::setUp();

        \Gdn::config()->saveToConfig("Signatures.Images.MaxNumber", 1, false);
    }

    /**
     * Test that signature can be set and are rendered on comments and discussions.
     */
    public function testRenderSignature()
    {
        $this->setCurrentUserSignature("Hello Signature");
        $discussion = $this->createDiscussion();
        $discussionUrl = $discussion["url"];
        $this->createComment();

        $html = $this->bessy()->getHtml($discussionUrl, [], [TestDispatcher::OPT_DELIVERY_TYPE => DELIVERY_TYPE_ALL]);
        $html->assertCssSelectorTextContains(".Discussion .UserSignature", "Hello Signature");
        $html->assertCssSelectorTextContains(".Comment .UserSignature", "Hello Signature");
        return $discussion["url"];
    }

    /**
     * Test that empty signatures do not render at all.
     *
     * @param string $discussionUrl Discussion url from the previous test.
     *
     * @depends testRenderSignature
     */
    public function testEmptySignatureNoRender(string $discussionUrl)
    {
        $this->setCurrentUserSignature("\n");

        $html = $this->bessy()->getHtml($discussionUrl, [], [TestDispatcher::OPT_DELIVERY_TYPE => DELIVERY_TYPE_ALL]);
        $html->assertCssSelectorNotExists(".Discussion .UserSignature", "Empty signature should not render.");
        $html->assertCssSelectorNotExists(".Comment .UserSignature", "Empty signature should not render.");
    }

    /**
     * Test that signature can be set and are rendered on comments and discussions without text, but with an Image.
     */
    public function testRenderSignatureImageOnly()
    {
        $this->setCurrentUserSignature('<img src="https://example.com/image.png" />', HtmlFormat::FORMAT_KEY);
        $discussion = $this->createDiscussion();
        $discussionUrl = $discussion["url"];
        $this->createComment();

        $html = $this->bessy()->getHtml($discussionUrl, [], [TestDispatcher::OPT_DELIVERY_TYPE => DELIVERY_TYPE_ALL]);
        $html->assertCssSelectorExists(".Discussion .UserSignature img");
        $html->assertCssSelectorExists(".Comment .UserSignature img");

        return $discussion["url"];
    }

    /**
     * Test that signature can be set and are rendered on comments and discussions.
     *
     * @param string $signature signature string.
     * @param string $format signature format, Rich/Text.
     * @param bool $expectedSuccess is the test expected to pass or fail.
     * @param string $expectValue resulting string.
     *
     * @dataProvider provideTestData
     */
    public function testSaveSignature(string $signature, string $format, bool $expectedSuccess, string $expectValue)
    {
        if (!$expectedSuccess) {
            $this->expectException(\Exception::class);
            $this->expectExceptionMessage($expectValue);
        }
        $result = $this->setCurrentUserSignature($signature, $format);
        $this->assertGreaterThan(0, $result->count());
    }

    /**
     * Provide groups test cases.
     *
     * @return array
     */
    public function provideTestData(): array
    {
        $r = [
            "Invalid Rich Format" => ["Hello Signature", RichFormat::FORMAT_KEY, false, "Signature invalid."],
            "Valid Text Format" => ["Hello Signature", TextFormat::FORMAT_KEY, true, "Your changes have been saved."],
            "Valid Rich Format" => [
                '[{"insert":"test 123\n"}]',
                RichFormat::FORMAT_KEY,
                true,
                "Your changes have been saved.",
            ],
        ];
        return $r;
    }

    /**
     * Set the current user's signature.
     *
     * @param string $signature
     * @param string $format
     */
    private function setCurrentUserSignature(string $signature, string $format = TextFormat::FORMAT_KEY)
    {
        return $this->bessy()->postJsonData("/profile/signature", [
            "Body" => $signature,
            "Format" => $format,
        ]);
    }

    /**
     * Test that the signature preferences are reachable.
     */
    public function testSignaturesProfilePage()
    {
        $response = $this->bessy()
            ->getJsonData("/profile/signature")
            ->asHttpResponse();
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * Test for signature expand.
     *
     * @return void
     */
    public function testSignatureExpand(): void
    {
        $userID = $this->createUserFixture(self::ROLE_MOD);

        $signature = "<p>My Signature</p>";
        $data = $this->runWithUser(function () use ($userID, $signature) {
            $result = [];
            $this->getSession()->addPermissions(["Plugins.Signatures.Edit"]);
            $this->setCurrentUserSignature($signature, HtmlFormat::FORMAT_KEY);
            $this->createDiscussion();
            $result["DiscussionID"] = $this->lastInsertedDiscussionID;
            $this->createComment();
            $result["CommentID"] = $this->lastInsertCommentID;
            return $result;
        }, $userID);

        // Now fetch the user and expand the signature
        $response = $this->api()->get("/users/{$userID}", ["expand" => ["signature"]]);
        $userData = $response->getBody();
        $this->assertArrayHasKey("signature", $userData);
        $this->assertEquals($signature, $userData["signature"]["body"]);

        // Now fetch the discussion and expand the signature
        $response = $this->api()->get("/discussions/{$data["DiscussionID"]}", ["expand" => "insertUser.signature"]);
        $discussionData = $response->getBody();
        $this->assertArrayHasKey("signature", $discussionData["insertUser"]);
        $this->assertEquals($signature, $discussionData["insertUser"]["signature"]["body"]);

        // Now fetch the comment and expand the signature
        $response = $this->api()->get("/comments/{$data["CommentID"]}", ["expand" => "insertUser.signature"]);
        $commentData = $response->getBody();
        $this->assertArrayHasKey("signature", $commentData["insertUser"]);
        $this->assertEquals($signature, $commentData["insertUser"]["signature"]["body"]);

        // Now test that signature is not shown for guest users
        $this->setConfig("Signatures.Hide.Guest", true);
        $this->runWithUser(function () use ($userID) {
            $response = $this->api()->get("/users/{$userID}", ["expand" => ["signature"]]);
            $userData = $response->getBody();
            $this->assertArrayNotHasKey("signature", $userData);
        }, 0);

        // Now test that signature is not shown for a user who has a rank that hides signatures
        $manualRank = $this->api()
            ->post("ranks", [
                "name" => "Signature Hider",
                "userTitle" => "Bummer",
                "level" => 2,
                "criteria" => ["manual" => true],
                "abilities" => [
                    "signature" => false,
                ],
            ])
            ->getBody();

        $this->api()->put("users/{$userID}/rank", ["rankID" => $manualRank["rankID"]]);

        // Get the user data
        $response = $this->api()->get("/users/{$userID}", ["expand" => ["signature"]]);
        $userData = $response->getBody();
        $this->assertArrayNotHasKey("signature", $userData);
        $this->assertEquals($manualRank["rankID"], $userData["rankID"]);
        $this->userModel->setField($userID, "RankID", null);
    }

    /**
     * Test user settings are accounted while expanding signature.
     * @return void
     */
    public function testUserSignatureSettingsAreValidatedBeforeSignatureExpand(): void
    {
        $userID = $this->createUserFixture(self::ROLE_MOD);

        $this->runWithUser(function () use ($userID) {
            $userModel = \Gdn::userMetaModel();
            $this->getSession()->addPermissions(["Plugins.Signatures.Edit"]);
            $signature = "<p>My Signature</p><img src=\"https://example.com/image.png\" />";
            $this->setCurrentUserSignature($signature, HtmlFormat::FORMAT_KEY);

            // Test we are stripping images from signature if the user has chosen to hide them
            $userModel->setUserMeta($userID, "Plugin.Signatures.HideImages", true);
            $response = $this->api()->get("/users/{$userID}", ["expand" => ["signature"]]);
            $userData = $response->getBody();

            $this->assertArrayHasKey("signature", $userData);
            $this->assertStringNotContainsString("<img", $userData["signature"]["body"]);
            $this->assertEquals("<p>My Signature</p>", $userData["signature"]["body"]);

            // Now test that signature is not shown for a user who has chosen to not view any signatures
            $userModel->setUserMeta($userID, "Plugin.Signatures.HideAll", true);
            $response = $this->api()->get("/users/{$userID}", ["expand" => ["signature"]]);
            $userData = $response->getBody();
            $this->assertArrayNotHasKey("signature", $userData);
            $this->assertEquals(200, $response->getStatusCode());
        }, $userID);
    }

    /**
     * Test that duplicated signatures do not break the site.
     *
     * @return void
     */
    public function testDuplicatedSignatures(): void
    {
        $userID = $this->createUserFixture(self::ROLE_MOD);

        // Directly insert the duplicated records.
        $database = \Gdn::database();
        $database->createSql()->insert("UserMeta", [
            [
                "UserID" => $userID,
                "Name" => "Plugin.Signatures.Sig",
                "Value" => "Hello Signature",
                "QueryValue" => "Plugin.Signatures.Sig.Hello Signature",
            ],
            [
                "UserID" => $userID,
                "Name" => "Plugin.Signatures.Sig",
                "Value" => "HIS SIGNATURE IS INVALID!",
                "QueryValue" => "Plugin.Signatures.HIS SIGNATURE IS INVALID!",
            ],
            [
                "UserID" => $userID,
                "Name" => "Plugin.Signatures.Format",
                "Value" => "Text",
                "QueryValue" => "Plugin.Signatures.Format.Text",
            ],
        ]);

        $this->runWithUser(function () use ($userID) {
            $discussion = $this->createDiscussion();
            $discussionUrl = $discussion["url"];
            $html = $this->bessy()->getHtml(
                $discussionUrl,
                [],
                [TestDispatcher::OPT_DELIVERY_TYPE => DELIVERY_TYPE_ALL]
            );
            $html->assertCssSelectorTextContains(".Discussion .UserSignature", "Hello Signature");
        }, $userID);
    }

    /**
     * Test that signatures without a format will fallback to the site default format.
     *
     * @return void
     */
    public function testMissingSignatureFormat(): void
    {
        $userID = $this->createUserFixture(self::ROLE_MOD);

        // Directly insert the duplicated records.
        $database = \Gdn::database();
        $database->createSql()->insert("UserMeta", [
            [
                "UserID" => $userID,
                "Name" => "Plugin.Signatures.Sig",
                "Value" =>
                    "[{\"type\":\"p\",\"children\":[{\"text\":\"Hello Signature\"}]},{\"type\":\"p\",\"children\":[{\"text\":\"\"}]}]",
                "QueryValue" => "Plugin.Signatures.Sig.Hello Signature",
            ],
        ]);

        $plugin = $this->container()->get(\SignaturesPlugin::class);
        $result = $plugin->getUsersSignature([$userID]);
        $this->assertEquals("<p>Hello Signature</p>", $result[$userID]["body"]);
    }

    /**
     * Test that the signature expand won't throw. We use an invalid rich2 payload to cause the exception.
     *
     * @return void
     */
    public function testInvalidFormatUserSignatureExpand(): void
    {
        $userID = $this->createUserFixture(self::ROLE_MOD);

        // Directly insert the duplicated records.
        $database = \Gdn::database();
        $database->createSql()->insert("UserMeta", [
            [
                "UserID" => $userID,
                "Name" => "Plugin.Signatures.Sig",
                "Value" => "Hello Signature",
                "QueryValue" => "Plugin.Signatures.Sig.Hello Signature",
            ],
            [
                "UserID" => $userID,
                "Name" => "Plugin.Signatures.Format",
                "Value" => "rich2",
                "QueryValue" => "Plugin.Signatures.Format.Text",
            ],
        ]);

        $expander = $this->container()->get(\UserSignatureExpander::class);
        $result = $expander->resolveFragments([$userID]);
        $this->assertEmpty($result);
    }
}
