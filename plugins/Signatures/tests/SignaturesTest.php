<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\Signatures;

use Vanilla\Formatting\Formats\TextFormat;
use VanillaTests\APIv0\TestDispatcher;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\SiteTestCase;

/**
 * Tests for signatures.
 */
class SignaturesTest extends SiteTestCase {

    use CommunityApiTestTrait;

    public static $addons = ['Signatures'];

    /**
     * Test that signature can be set and are rendered on comments and discussions.
     */
    public function testRenderSignature() {
        $this->setCurrentUserSignature('Hello Signature');
        $discussion = $this->createDiscussion();
        $discussionUrl = $discussion['url'];
        $this->createComment();

        $html = $this->bessy()->getHtml($discussionUrl, [], [TestDispatcher::OPT_DELIVERY_TYPE => DELIVERY_TYPE_ALL]);
        $html->assertCssSelectorTextContains(".Discussion .UserSignature", "Hello Signature");
        $html->assertCssSelectorTextContains(".Comment .UserSignature", "Hello Signature");
        return $discussion['url'];
    }

    /**
     * Test that empty signatures do not render at all.
     *
     * @param string $discussionUrl Discussion url from the previous test.
     *
     * @depends testRenderSignature
     */
    public function testEmptySignatureNoRender(string $discussionUrl) {
        $this->setCurrentUserSignature("\n");

        $html = $this->bessy()->getHtml($discussionUrl, [], [TestDispatcher::OPT_DELIVERY_TYPE => DELIVERY_TYPE_ALL]);
        $html->assertCssSelectorNotExists(".Discussion .UserSignature", "Empty signature should not render.");
        $html->assertCssSelectorNotExists(".Comment .UserSignature", "Empty signature should not render.");
    }


    /**
     * Set the current user's signature.
     *
     * @param string $signature
     */
    private function setCurrentUserSignature(string $signature) {
        $this->bessy()->postJsonData('/profile/signature', [
            'Body' => $signature,
            'Format' => TextFormat::FORMAT_KEY,
        ]);
    }
}
