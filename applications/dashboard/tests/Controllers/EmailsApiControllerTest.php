<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Dashboard\Controllers;

use Vanilla\Dashboard\Controllers\Api\EmailsApiController;
use VanillaTests\Fixtures\Html\TestHtmlDocument;
use VanillaTests\SiteTestCase;

/**
 * Tests for /api/v2/emails
 * {@see EmailsApiController}
 */
class EmailsApiControllerTest extends SiteTestCase
{
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
}
