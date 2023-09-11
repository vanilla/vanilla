<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Web;

use PHPUnit\Framework\TestCase;
use Vanilla\Web\Asset\ExternalAsset;
use Vanilla\Web\ContentSecurityPolicy\ContentSecurityPolicyModel;
use Vanilla\Web\PageHead;
use Vanilla\Web\PageHeadInterface;
use VanillaTests\Fixtures\PageFixture;
use VanillaTests\SiteTestCase;
use VanillaTests\SiteTestTrait;

/**
 * Tests for the page head.
 */
class PageHeadTest extends SiteTestCase
{
    /**
     * Test that our proxy works the same as actual head.
     */
    public function testProxy()
    {
        /** @var PageFixture $page */
        $page = self::container()->get(PageFixture::class);

        /** @var PageHead $head */
        $head = self::container()->get(PageHead::class);

        $head->setAssetSection($page->getAssetSection());

        $instances = [$page, $head];

        /** @var PageHeadInterface $instance */
        foreach ($instances as $instance) {
            $instance
                ->addScript(new ExternalAsset("https://test.com/javascript.js"))
                ->addInlineScript("console.log('Hello world')")
                ->addLinkTag(["rel" => "isLink"])
                ->addMetaTag(["type" => "isMeta"])
                ->addOpenGraphTag("og:isOg", "ogContent")
                ->setSeoTitle("Test seo")
                ->setSeoDescription("test test test")
                ->setSeoBreadcrumbs([])
                ->setCanonicalUrl("http://canonical.com");
        }

        $headHtml = $head->renderHtml();
        $pageHeadHtml = $page->getHead()->renderHtml();
        $this->assertEquals($headHtml, $pageHeadHtml);
    }

    /**
     * Test that our config meta values are adequately sanitized.
     *
     * @param string $configValue
     * @param string $expected
     * @param string[] $expectedNotToHave
     *
     * @dataProvider provideMetas
     */
    public function testMetaTags(string $configValue, string $expected, array $expectedNotToHave = [])
    {
        $this->runWithConfig(["seo.metaHtml" => $configValue], function () use ($expected, $expectedNotToHave) {
            $head = self::container()->get(PageHead::class);
            $head->setAssetSection("someSection");
            $headHtml = $head->renderHtml()->jsonSerialize();
            $this->assertStringContainsString($expected, $headHtml);
            foreach ($expectedNotToHave as $strExpectedNotToHave) {
                $this->assertStringNotContainsString($strExpectedNotToHave, $headHtml);
            }
        });
    }

    /**
     * @return iterable
     */
    public function provideMetas(): iterable
    {
        $msMeta = '<meta name="msvalidate.01" content="567EA6180B40F6D0B291E5F61E5337B2" />';
        $googleMeta =
            '<meta name="google-site-verification" content="+nxGUDJ4QpAZ5l9Bsjdi102tLVC21AIh5d1Nl23908vVuFHs34=" />';
        $script = "<script>console.log('hello world');</script>";
        $style = "<style>.thing {color: red;}</style>";
        $injectionAttempt = "/>{$script}";

        yield "allows well formed metas" => [
            implode("\n", [$msMeta, $googleMeta]),
            implode("\n", [$msMeta, $googleMeta]),
        ];

        yield "ignores non meta tags" => [
            implode("\n", [$googleMeta, $script, $style]),
            $googleMeta,
            [$script, $style],
        ];

        yield "injection attempt" => [$injectionAttempt, "", [$script]];

        yield "meta bodies are excluded" => [
            '<meta name="test" content="hello">In the body</meta>',
            '<meta name="test" content="hello" />',
            ["In the body"],
        ];

        // This is just doesn't blow up.
        yield "invalidHtml" => ["<<<<<<<<4A>><'\"\"\"\">SdF>4<1>,12,4#!@$#%@$#^", ""];
    }
}
