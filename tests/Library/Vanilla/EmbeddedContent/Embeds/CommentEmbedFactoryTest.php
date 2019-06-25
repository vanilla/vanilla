<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\EmbeddedContent\Embeds;

use Garden\Web\RequestInterface;
use Vanilla\EmbeddedContent\Factories\CommentEmbedFactory;
use VanillaTests\APIv2\AbstractAPIv2Test;

/**
 * Tests for the comment/quote embed.
 */
class CommentEmbedFactoryTest extends AbstractAPIv2Test {

    /** @var CommentEmbedFactory */
    private $factory;

    /** @var \CommentsApiController */
    private $commentsApi;

    /**
     * Set the factory and client.
     */
    public function setUp() {
        parent::setUp();
        $this->commentsApi = $this->createMock(\CommentsApiController::class);
        $request = self::container()->get(RequestInterface::class);
        $this->factory = new CommentEmbedFactory($request, $this->commentsApi);
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
            [static::bootstrap()->getBaseUrl() . '/discussion/comment/41342', true, "It should match a proper URL"],
            // Not allowed
            ['http://vanilla.test' . '/discussion/comment/41342', false, "It should match a proper URL"],
            ['https://otherdomain.com' . '/discussion/comment/41342', false, "It should fail on a bad domain."],
            [static::bootstrap()->getBaseUrl() . '/discussions/comments/41342', false, "It should fail on a bad path 1."],
            [static::bootstrap()->getBaseUrl() . '/discussion/41342', false, "It should fail on a bad path 2"],
            [static::bootstrap()->getBaseUrl() . '/discussion/comment/asdfads', false, "It should fail on a bad ID."],
        ];
    }

    /**
     * Test network request fetching and handling.
     */
    public function testCreateEmbedForUrl() {
    }
}
