<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\EmbeddedContent;

use Vanilla\EmbeddedContent\EmbedService;
use VanillaTests\MinimalContainerTestCase;
use VanillaTests\Fixtures\EmbeddedContent\MockEmbed;
use VanillaTests\Fixtures\EmbeddedContent\MockEmbedFactory;
use VanillaTests\Fixtures\NullCache;

/**
 * Tests for the EmbedService class.
 */
class EmbedServiceTest extends MinimalContainerTestCase {

    /** @var EmbedService */
    private $embedService;

    /**
     * Setup the container with a null cache.
     */
    public function setUp() {
        parent::setUp();
        $container = \Gdn::getContainer();
        $container->rule(\Gdn_Cache::class)
            ->setClass(NullCache::class);
        $this->resetEmbedService();
    }

    /**
     * Get a new copy of the embed service.
     */
    private function resetEmbedService() {
        $container = \Gdn::getContainer();
        $this->embedService = $container->get(EmbedService::class);
    }

    /**
     * Create an embed and factory and return them as a tuple.
     *
     * @param bool $pathSupport
     *
     * @return array
     */
    private function makeEmbedAndFactory(bool $pathSupport = false): array {
        $embed = MockEmbed::nullEmbed();
        $factory = new MockEmbedFactory($embed);
        $factory->setSupportedDomains(['test.com']);
        $factory->setCanHandleEmptyPaths(!$pathSupport);
        if ($pathSupport) {
            $factory->setSupportedPathRegex('/.*/');
        } else {
            $factory->setSupportedPathRegex('/^$/');
        }
        return [$embed, $factory];
    }

    /**
     * Test that registration/priority system works.
     */
    public function testRegistration() {
        [$embed1, $factory1] = $this->makeEmbedAndFactory();
        [$embed2, $factory2] = $this->makeEmbedAndFactory();

        // Registration.
        $this->embedService->registerFactory($factory1);
        $this->embedService->registerFactory($factory2);

        $url = 'https://test.com';
        $urlEmbed = $this->embedService->createEmbedForUrl($url);
        $this->assertSame(
            $urlEmbed,
            $embed1,
            'Factories registered first should match first'
        );

        // Priorities.
        $this->resetEmbedService();
        $this->embedService->registerFactory($factory1);
        $this->embedService->registerFactory($factory2, EmbedService::PRIORITY_HIGH);

        $urlEmbed = $this->embedService->createEmbedForUrl($url);
        $this->assertSame(
            $urlEmbed,
            $embed2,
            'High priority items are respected'
        );

        // Priorities.
        $this->resetEmbedService();
        $this->embedService->registerFactory($factory1, EmbedService::PRIORITY_LOW);
        $this->embedService->registerFactory($factory2);

        $urlEmbed = $this->embedService->createEmbedForUrl($url);
        $this->assertSame(
            $urlEmbed,
            $embed2,
            'Low priority items are respected'
        );
    }

    /**
     * Test that the fallback is properly called.
     */
    public function testFallback() {
        [$embed1, $factory1] = $this->makeEmbedAndFactory(false);
        [$fallbackEmbed, $fallbackFactory] = $this->makeEmbedAndFactory(true);

        // Registration.
        $this->embedService->registerFactory($factory1);
        $this->embedService->setFallbackFactory($fallbackFactory);

        $url = 'https://test.com/asdfasdf';
        $urlEmbed = $this->embedService->createEmbedForUrl($url);
        $this->assertSame(
            $urlEmbed,
            $fallbackEmbed,
            'The fallback embed should be returned.'
        );
    }
}
