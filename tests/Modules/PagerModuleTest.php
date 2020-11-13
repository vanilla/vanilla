<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Modules;

use PagerModule;
use PHPUnit\Framework\TestCase;
use VanillaTests\BootstrapTrait;
use VanillaTests\SiteTestTrait;
use VanillaTests\Library\Vanilla\Formatting\HtmlNormalizeTrait;

/**
 * Tests for the `PagerModule`.
 */
class PagerModuleTest extends TestCase {

    use SiteTestTrait;
    use HtmlNormalizeTrait;

    /**
     * @inheritDoc
     */
    public function setUp(): void {
        parent::setUp();

        $this->controller = self::container()->get(\DiscussionsController::class);
        /** @psalm-suppress UndefinedClass */
        $this->controller->Menu = new \MenuModule();
        $this->controller->initialize();
        \Gdn::controller($this->controller);
    }

    /**
     * A simple slash should always format properly.
     */
    public function testFormatUrlSlash(): void {
        $url = PagerModule::formatUrl('/', '');
        $this->assertSame('/', $url);
    }

    /**
     * An empty page parameter should trim a trailing slash.
     */
    public function testFormatUrlEmptyPage(): void {
        $url = PagerModule::formatUrl('/discussions/{Page}', '');
        $this->assertSame('/discussions', $url);
    }

    /**
     * If the URL format has a trailing slash then it should not be trimmed.
     */
    public function testFormatUrlTrailing(): void {
        $url = PagerModule::formatUrl('/discussions/', '');
        $this->assertSame('/discussions/', $url);
    }

    /**
     * The page parameter should show up in a formatted URL.
     */
    public function testFormatUrlRegular(): void {
        $url = PagerModule::formatUrl('/discussions/{Page}', 'p2');
        $this->assertSame('/discussions/p2', $url);
    }

    /**
     * Test configuring a PagerModule
     *
     * @param array $params
     * @param string $expectedHtml
     * @dataProvider provideTestConfigure
     */
    public function testConfigure(array $params, string $expectedHtml) {
        $pagerModule = new PagerModule($this->controller);
        $pagerModule->configure(...$params);
        $this->assertHtmlStringEqualsHtmlString($expectedHtml, $pagerModule);
    }

    /**
     * @return array
     */
    public function provideTestConfigure() {
        return [
            'simple pager' => [
                [0, 30, 100, '/discussions/{Page}'],
                <<<EOT
<div class="PagerWrap">
    <div aria-label="Pagination - Bottom" class="NumberedPager Pager PagerLinkCount-6" id="PagerAfter" role="navigation">
        <span aria-disabled="true" class="Pager-nav Previous">«</span>
        <a aria-current="page" aria-label="Page 1" class="FirstPage Highlight Pager-p p-1" href="/pagermoduletest/discussions" tabindex="0">1</a>
        <a aria-label="Page 2" class="Pager-p p-2" href="/pagermoduletest/discussions/p2" rel="next" tabindex="0">2</a>
        <a aria-label="Page 3" class="Pager-p p-3" href="/pagermoduletest/discussions/p3" tabindex="0">3</a>
        <a aria-label="Page 4" class="LastPage Pager-p p-4" href="/pagermoduletest/discussions/p4" tabindex="0">4</a>
        <a aria-label="Next Page" class="Next" href="/pagermoduletest/discussions/p2" rel="next" tabindex="0" title="Next Page">»</a>
    </div>
</div>
EOT
            ]
        ];
    }
}
