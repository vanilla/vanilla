<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\EmbeddedContent\Factories;

use Garden\Web\RequestInterface;
use Vanilla\Contracts\Site\SiteSectionInterface;
use Vanilla\EmbeddedContent\Factories\CommentEmbedFactory;
use Vanilla\Site\DefaultSiteSection;
use VanillaTests\APIv2\AbstractAPIv2Test;
use VanillaTests\Fixtures\MockConfig;
use VanillaTests\Fixtures\MockSiteSection;
use VanillaTests\Fixtures\MockSiteSectionProvider;

/**
 * Tests for the comment/quote embed.
 */
class CommentEmbedFactoryTest extends AbstractAPIv2Test {

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
        $commentsApi = $this->createMock(\CommentsApiController::class);

        /** @var RequestInterface $request */
        $request = self::container()->get(RequestInterface::class);
        $request->setAssetRoot($customRoot);

        $sectionProvider = new MockSiteSectionProvider(new DefaultSiteSection(new MockConfig()));
        $sectionProvider->addSiteSections($siteSections);

        $factory = new CommentEmbedFactory($request, $sectionProvider, $commentsApi);

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
                $bootstrapBase . '/discussion/comment/41342',
                true
            ],
            // Not allowed
            'Correct webroot' => [
                $bootstrapBase . '/actual-root/discussion/comment/41342',
                true,
                '/actual-root'
            ],
            'Correct section' => [
                $bootstrapBase . '/actual-root/actual-section/discussion/comment/41342',
                true,
                '/actual-root',
                [new MockSiteSection('test', 'en', '/actual-section', 'test1', 'test1')]
            ],
            // Not allowed
            'Wrong webroot' => [
                $bootstrapBase . '/wrong-root/discussion/comment/41342',
                false,
                '/actual-root'
            ],
            'Wrong section' => [
                $bootstrapBase . '/actual-root/actual-section/discussion/comment/41342',
                false,
                '/actual-root',
            ],
            'wrong host' => [
                'https://otherdomain.com/discussion/comment/41342',
                false
            ],
            'wrong url (typo)' => [
                $bootstrapBase . '/discussions/comments/41342',
                false
            ],
            'Wrong url (discussion)' => [
                $bootstrapBase . '/discussion/41342',
                false
            ],
            'bad ID' => [
                $bootstrapBase . '/discussion/comment/asdfads',
                false
            ],
        ];
    }
}
