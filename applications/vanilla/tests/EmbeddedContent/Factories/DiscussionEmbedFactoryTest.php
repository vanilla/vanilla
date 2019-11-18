<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Forum\EmbeddedContent\Factories;

use Garden\Web\RequestInterface;
use Vanilla\Contracts\Site\SiteSectionInterface;
use Vanilla\Forum\EmbeddedContent\Factories\DiscussionEmbedFactory;
use Vanilla\Site\DefaultSiteSection;
use Vanilla\Site\SiteSectionModel;
use VanillaTests\APIv2\AbstractAPIv2Test;
use VanillaTests\Fixtures\MockConfig;
use VanillaTests\Fixtures\MockSiteSection;
use VanillaTests\Fixtures\MockSiteSectionProvider;

/**
 * Tests for the discussion/quote embed.
 */
class DiscussionEmbedFactoryTest extends AbstractAPIv2Test {

    /**
     * Test that all domain types are supported.
     *
     * @param string $urlToTest
     * @param bool $isSupported
     * @param string $customRoot
     * @param SiteSectionInterface[] $siteSections
     * @dataProvider supportedDomainsProvider
     */
    public function testSupportedDomains(string $urlToTest, bool $isSupported, string $customRoot = '', array $siteSections = []) {
        $discussionApi = $this->createMock(\DiscussionsApiController::class);

        /** @var RequestInterface $request */
        $request = self::container()->get(RequestInterface::class);
        $request->setAssetRoot($customRoot);

        $sectionProvider = new MockSiteSectionProvider(new DefaultSiteSection(new MockConfig()));
        $sectionProvider->addSiteSections($siteSections);
        $sectionModel = new SiteSectionModel(new MockConfig());
        $sectionModel->addProvider($sectionProvider);

        $factory = new DiscussionEmbedFactory($request, $sectionModel, $discussionApi);

        $this->assertEquals($isSupported, $factory->canHandleUrl($urlToTest));
    }

    /**
     * @return array
     */
    public function supportedDomainsProvider(): array {
        $bootstrapBase = 'http://vanilla.test';
        return [
            // Allowed
            'Correct' => [
                $bootstrapBase . '/discussion/41342',
                true
            ],
            // Not allowed
            'Correct webroot' => [
                $bootstrapBase . '/actual-root/discussion/41342',
                true,
                '/actual-root'
            ],
            'Correct section' => [
                $bootstrapBase . '/actual-root/actual-section/discussion/41342',
                true,
                '/actual-root',
                [new MockSiteSection('test', 'en', '/actual-section', 'test1', 'test1')]
            ],
            // Not allowed
            'Wrong webroot' => [
                $bootstrapBase . '/wrong-root/discussion/41342',
                false,
                '/actual-root'
            ],
            'Wrong section' => [
                $bootstrapBase . '/actual-root/actual-section/discussion/41342',
                false,
                '/actual-root',
            ],
            'wrong host' => [
                'https://otherdomain.com/discussion/41342',
                false
            ],
            'wrong url (typo)' => [
                $bootstrapBase . '/discussions/41342',
                false
            ],
            'Wrong url (is a comment)' => [
                $bootstrapBase . '/discussion/comment/41342',
                false
            ],
            'bad ID' => [
                $bootstrapBase . '/discussion/asdfads',
                false
            ],
        ];
    }
}
