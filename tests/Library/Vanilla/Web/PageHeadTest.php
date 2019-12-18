<?php

use PHPUnit\Framework\TestCase;
use Vanilla\Web\Asset\ExternalAsset;
use Vanilla\Web\ContentSecurityPolicy\ContentSecurityPolicyModel;
use Vanilla\Web\PageHead;
use Vanilla\Web\PageHeadInterface;
use VanillaTests\Fixtures\PageFixture;
use VanillaTests\SiteTestTrait;

/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

/**
 * Tests for the page head.
 */
class PageHeadTest extends TestCase {

    use SiteTestTrait;

    /**
     * Test that our proxy works the same as actual head.
     */
    public function testProxy() {

        // Make sure we use the same CSP model between tests (same nonce).
        self::container()
            ->rule(ContentSecurityPolicyModel::class)
            ->setShared(true)
        ;

        /** @var PageFixture $page */
        $page = self::container()->get(PageFixture::class);

        /** @var PageHead $head */
        $head = self::container()->get(PageHead::class);

        $head->setAssetSection($page->getAssetSection());

        $instances = [$page, $head];

        /** @var PageHeadInterface $instance */
        foreach ($instances as $instance) {
            $instance
                ->addScript(new ExternalAsset('https://test.com/javascript.js'))
                ->addInlineScript("console.log('Hello world')")
                ->addLinkTag(['rel' => 'isLink'])
                ->addMetaTag(['type' => 'isMeta'])
                ->addOpenGraphTag('og:isOg', 'ogContent')
                ->setSeoTitle('Test seo')
                ->setSeoDescription('test test test')
                ->setSeoBreadcrumbs([])
                ->setCanonicalUrl('http://canonical.com')
            ;
        }

        $headHtml = $head->renderHtml();
        $pageHeadHtml = $page->getHead()->renderHtml();
        $this->assertEquals($headHtml, $pageHeadHtml);
    }
}
