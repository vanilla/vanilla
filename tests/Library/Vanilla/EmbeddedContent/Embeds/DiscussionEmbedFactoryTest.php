<?php


/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\EmbeddedContent\Embeds;

use Garden\Web\RequestInterface;
use Vanilla\EmbeddedContent\Embeds\CommentEmbedFactory;
use Vanilla\EmbeddedContent\Embeds\DiscussionEmbedFactory;
use VanillaTests\APIv2\AbstractAPIv2Test;

/**
 * Tests for the discussion/quote embed.
 */
class DiscussionEmbedFactoryTest extends AbstractAPIv2Test {

    /** @var CommentEmbedFactory */
    private $factory;

    /** @var \DiscussionsApiController */
    private $discussionApi;

    /**
     * Set the factory and client.
     */
    public function setUp() {
        parent::setUp();
        $this->discussionApi = $this->createMock(\DiscussionsApiController::class);
        $request = self::container()->get(RequestInterface::class);
        $this->factory = new DiscussionEmbedFactory($request, $this->discussionApi);
    }


    /**
     * Test that all giphy domain types are supported.
     */
    public function testSupportedDomains() {
        foreach ($this->supportedDomainsProvider() as [$urlToTest, $isSupported, $message]) {
            $this->assertEquals($this->factory->canHandleUrl($urlToTest), $isSupported, $message);
        }
    }

    /**
     * @return array
     */
    public function supportedDomainsProvider(): array {
        return [
            // Allowed
            [static::bootstrap()->getBaseUrl() . '/discussion/41342', true, "It should match a proper URL"],
            // Not allowed
            ['http://vanilla.test' . '/discussion/41342', false, "It should fail on a missing subpath."],
            ['https://otherdomain.com' . '/discussion/41342', false, "It should fail on a bad domain."],
            [static::bootstrap()->getBaseUrl() . '/discussions/comments/41342', false, "It should fail on a bad path 1."],
            [static::bootstrap()->getBaseUrl() . '/discussion/comment/41342', false, "It should fail on a bad path 2"],
            [static::bootstrap()->getBaseUrl() . '/discussion/asdfads', false, "It should fail on a bad ID."],
        ];
    }

    /**
     * Test network request fetching and handling.
     */
    public function testCreateEmbedForUrl() {
    }
}
