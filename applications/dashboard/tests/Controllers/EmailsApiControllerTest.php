<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Dashboard\Controllers;

use Vanilla\Dashboard\Controllers\Api\EmailsApiController;
use VanillaTests\Fixtures\Html\TestHtmlDocument;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\SiteTestCase;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Tests for /api/v2/emails
 * {@see EmailsApiController}
 */
class EmailsApiControllerTest extends SiteTestCase
{
    use UsersAndRolesApiTestTrait;
    use CommunityApiTestTrait;

    /**
     * Test that the email preview reflects the parameters given to it.
     */
    public function testEmailPreview()
    {
        $response = $this->api()->post("/emails/preview", $this->getEmailPreviewData());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("text/html", $response->getHeader("content-type"));
        $this->assertEmailPreviewData($response->getBody());
    }

    /**
     * Test that the /api/v2/emails/send-test endpoint sends a preview email reflecting the parameters passed.
     */
    public function testSendPreview()
    {
        $response = $this->api()->post(
            "/emails/send-test",
            $this->getEmailPreviewData() + ["destinationAddress" => "test@myemail.com"]
        );
        $this->assertEquals(201, $response->getStatusCode());

        $sentEmail = $this->assertEmailSentTo("test@myemail.com");
        $this->assertEmailPreviewData($sentEmail->template->toString());
    }

    /**
     * @return array
     */
    private function getEmailPreviewData(): array
    {
        return [
            "emailFormat" => "html",
            "templateStyles" => [
                "backgroundColor" => "#010101",
                "textColor" => "#efefef",
            ],
            "footer" => '[{"type":"p","children":[{"text":"hello footer"}]}]',
        ];
    }

    /**
     * @param string $emailBody
     * @return void
     */
    private function assertEmailPreviewData(string $emailBody)
    {
        $html = new TestHtmlDocument($emailBody, false);
        $body = $html->queryCssSelector("body")->item(0);
        $this->assertInstanceOf(\DOMElement::class, $body);
        $this->assertEquals("#010101", $body->getAttribute("bgcolor"));
        $this->assertStringContainsString("color: #efefef", $body->getAttribute("style"));

        $footer = $html->queryCssSelector(".footer")->item(0);
        $this->assertInstanceOf(\DOMElement::class, $footer);
        $this->assertEquals("hello footer", trim($footer->textContent));
    }

    /**
     * Test that the digest endpoint requires a feature flag.
     */
    public function testSendTestDigestFails(): void
    {
        $this->expectExceptionMessage("This feature is not enabled.");
        $response = $this->api()->post("/emails/send-test-digest", [
            "destinationAddress" => "dest@example.com",
            "destinationUserID" => $this->api()->getUserID(),
        ]);
    }

    /**
     * Test sending test digest emails.
     */
    public function testSendTestDigestEmail(): void
    {
        self::enableFeature("Digest");

        $user = $this->createUser([
            "email" => "testuser@example.com",
        ]);

        $anotherUser = $this->createUser();

        $this->resetTable("Discussion");
        $this->resetTable("Comment");
        $cat1 = $this->createCategory(["name" => "My Cat 1"]);
        $disc1 = $this->createDiscussion(["name" => "My Disc 1"]);
        $cat2 = $this->createCategory(["name" => "My Cat 2"]);
        $disc2 = $this->createDiscussion(["name" => "My Disc 2"]);
        $cat3 = $this->createCategory(["name" => "My Cat 3"]);
        $disc3 = $this->createDiscussion(["name" => "My Disc 3"]);

        // These should not appear in the digest.
        $permCat = $this->createPermissionedCategory();
        $hiddenDisc = $this->createDiscussion(["name" => "Hide me"], ["Score" => 100]);

        $response = self::runWithConfig(
            [
                // Works even with digest disabled.
                "Garden.Digest.Enabled" => false,
            ],
            function () use ($user) {
                return $this->api()->post("/emails/send-test-digest", [
                    "destinationAddress" => "dest@example.com",
                    "destinationUserID" => $user["userID"],
                    "emailFormat" => "html", // We can use a dynamic email format.
                ]);
            }
        );

        $this->assertEquals(201, $response->getStatusCode());
        // Ensure no email was sent to the actual user email since we provided the destination address.
        $this->assertEmailNotSentTo("testuser@example.com");
        $email = $this->assertEmailSentTo("dest@example.com");

        // I've received a content
        $emailHtml = $email->getHtmlDocument();
        $emailHtml->assertContainsLink($cat1["url"], $cat1["name"]);
        $emailHtml->assertContainsLink($cat2["url"], $cat2["name"]);
        $emailHtml->assertContainsLink($cat3["url"], $cat3["name"]);
        $emailHtml->assertContainsLink($disc1["url"], $disc1["name"]);
        $emailHtml->assertContainsLink($disc2["url"], $disc2["name"]);
        $emailHtml->assertContainsLink($disc3["url"], $disc3["name"]);
        $emailHtml->assertNotContainsString("Hide me");
    }

    /**
     * Test sending test digest emails, does not include categories followed by other users.
     */
    public function testSendTestDigestEmailCheckCategory(): void
    {
        self::enableFeature("Digest");

        $user = $this->createUser([
            "email" => "testuser1@example.com",
        ]);

        $anotherUser = $this->createUser();

        $this->resetTable("Discussion");
        $this->resetTable("Comment");
        $cat1 = $this->createCategory(["name" => "My Cat 1"]);
        $disc1 = $this->createDiscussion(["name" => "My Disc 1"]);
        $cat4 = $this->createCategory(["name" => "My Cat 4"]);
        $hiddenDisc = $this->createDiscussion(["name" => "Extra User following"], ["Score" => 100]);
        // have a different user follow category should not affect digest for $user
        self::runWithConfig(["Garden.Digest.Enabled" => true], function () use ($anotherUser, $cat4) {
            $this->setCategoryPreference($anotherUser, $cat4, [
                \CategoriesApiController::OUTPUT_PREFERENCE_FOLLOW => true,
                \CategoriesApiController::OUTPUT_PREFERENCE_DISCUSSION_APP => true,
                \CategoriesApiController::OUTPUT_PREFERENCE_DIGEST => true,
            ]);
        });
        self::runWithConfig(["Garden.Digest.Enabled" => true], function () use ($user, $cat1) {
            $this->setCategoryPreference($user, $cat1, [
                \CategoriesApiController::OUTPUT_PREFERENCE_FOLLOW => true,
                \CategoriesApiController::OUTPUT_PREFERENCE_DISCUSSION_APP => true,
                \CategoriesApiController::OUTPUT_PREFERENCE_DIGEST => true,
            ]);
        });

        $response = self::runWithConfig(
            [
                // Works even with digest disabled.
                "Garden.Digest.Enabled" => false,
            ],
            function () use ($user) {
                return $this->api()->post("/emails/send-test-digest", [
                    "destinationAddress" => "dest@example.com",
                    "destinationUserID" => $user["userID"],
                    "emailFormat" => "html", // We can use a dynamic email format.
                ]);
            }
        );

        $this->assertEquals(201, $response->getStatusCode());
        // Ensure no email was sent to the actual user email since we provided the destination address.
        $this->assertEmailNotSentTo("testuser1@example.com");
        $email = $this->assertEmailSentTo("dest@example.com");

        // I've received a content
        $emailHtml = $email->getHtmlDocument();
        $emailHtml->assertContainsLink($cat1["url"], $cat1["name"]);
        $emailHtml->assertContainsLink($disc1["url"], $disc1["name"]);
        $emailHtml->assertNotContainsString("Extra User following");
    }
}
