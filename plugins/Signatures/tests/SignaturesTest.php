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

/**
 * Tests for signatures.
 */
class SignaturesTest extends SiteTestCase
{
    use CommunityApiTestTrait;

    public static $addons = ["Signatures"];

    /**
     * @inheritDoc
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
}
