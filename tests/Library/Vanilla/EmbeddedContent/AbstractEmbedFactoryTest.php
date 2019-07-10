<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\EmbeddedContent;

use VanillaTests\ContainerTestCase;
use VanillaTests\Fixtures\EmbeddedContent\MockEmbed;
use VanillaTests\Fixtures\EmbeddedContent\MockEmbedFactory;

/**
 * Tests for the embed factory.
 */
class AbstractEmbedFactoryTest extends ContainerTestCase {

    /**
     * @inheritdoc
     */
    public function setUp() {
        parent::setUp();
    }

    /**
     * @return MockEmbedFactory
     */
    private function setupEmbedFactory(): MockEmbedFactory {
        $factory = new MockEmbedFactory(MockEmbed::nullEmbed());
        $factory->setSupportedDomains(['test.com', 'nested.domain.com']);
        $factory->setSupportedPathRegex('/.*/');
        $factory->setCanHandleEmptyPaths(true);
        return $factory;
    }

    /**
     * Test positive uses of canHandleUrl.
     */
    public function testCanHandleUrlPositive() {
        $factory = $this->setupEmbedFactory();
        $this->assertTrue($factory->canHandleUrl('http://test.com'));
        $this->assertTrue($factory->canHandleUrl('https://test.com'));
        $this->assertTrue($factory->canHandleUrl('https://nested.domain.com/asdfasdf'));
    }

    /**
     * Test negative assertions for URL matching.
     */
    public function testCanHandleUrlNegative() {
        $factory = $this->setupEmbedFactory();
        $this->assertFalse(
            $factory->canHandleUrl('ftp://test.com'),
            'It rejects an improper scheme'
        );
        $this->assertFalse(
            $factory->canHandleUrl('https://domain.com/asdfasdf'),
            'It rejects parent domains when only a subdomain is specified'
        );

        $factory->setCanHandleEmptyPaths(false);
        $this->assertFalse(
            $factory->canHandleUrl('https://test.com'),
            'It rejects empty paths when the flag is not set.'
        );

        $factory->setSupportedPathRegex('/testtest/');
        $this->assertFalse(
            $factory->canHandleUrl('https://test.com/asdfasdf'),
            'It rejects when the regex doesn\'t match.'
        );
    }
}
