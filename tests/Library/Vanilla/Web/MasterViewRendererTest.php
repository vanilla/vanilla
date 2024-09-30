<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Web;

use Garden\Web\Exception\NotFoundException;
use Vanilla\Layout\LayoutPage;
use Vanilla\Site\DefaultSiteSection;
use Vanilla\Site\SiteSectionModel;
use Vanilla\Web\Events\PageRenderBeforeEvent;
use Vanilla\Web\MasterViewRenderer;
use Vanilla\Web\Page;
use Vanilla\Web\SimpleTitlePage;
use VanillaTests\BootstrapTestCase;
use VanillaTests\EventSpyTestTrait;
use VanillaTests\Fixtures\Html\TestHtmlDocument;
use VanillaTests\Fixtures\MockConfig;
use VanillaTests\Fixtures\MockSiteSection;
use VanillaTests\Fixtures\MockSiteSectionProvider;
use VanillaTests\SiteTestCase;

/**
 * Tests for the master view renderer.
 */
class MasterViewRendererTest extends SiteTestCase
{
    use EventSpyTestTrait;

    protected static $enabledLocales = ["vf_fr" => "fr", "vf_ar" => "ar"];

    /**
     * Test that our render event is fired.
     */
    public function testRenderEvent()
    {
        /** @var MasterViewRenderer $renderer */
        $renderer = self::container()->get(MasterViewRenderer::class);
        /** @var Page $page */
        $page = self::container()->get(SimpleTitlePage::class);

        $page->initialize();

        $result = $renderer->renderPage($page, []);
        $testHtml = new TestHtmlDocument($result);
        $testHtml->assertCssSelectorExists('meta[type="custom"]');
    }

    /**
     * Event to modify the head during render.
     *
     * @param PageRenderBeforeEvent $event
     */
    public function pageRenderBefore_handler(PageRenderBeforeEvent $event)
    {
        $event->getPageHead()->addMetaTag(["type" => "custom"]);
    }

    /**
     * Test we have direction Right To Left applied on html with different (config, layout/legacy page) cases.
     */
    public function testRTLDirection()
    {
        $section_english = new MockSiteSection(
            "siteSectionName_en",
            "en",
            "/en",
            "mockSiteSection-en",
            "mockSiteSectionGroup-1",
            [
                "Destination" => "discussions",
                "Type" => "Internal",
            ],
            "keystone"
        );
        $section_arabic = new MockSiteSection(
            "siteSectionName_ar",
            "ar",
            "/ar",
            "mockSiteSection-ar",
            "mockSiteSectionGroup-2",
            [
                "Destination" => "discussions",
                "Type" => "Internal",
            ],
            "foundation"
        );

        $router = self::container()->get(\Gdn_Router::class);
        $defaultSection = new DefaultSiteSection(new MockConfig(), $router);
        $this->setConfig("minify.html", true);

        /** @var SiteSectionModel $siteSectionModel */
        $siteSectionModel = self::container()->get(SiteSectionModel::class);
        $siteSectionProvider = new MockSiteSectionProvider($defaultSection);
        $siteSectionProvider->addSiteSections([$section_english, $section_arabic]);
        $siteSectionProvider->setCurrentSiteSection($section_arabic);
        $siteSectionModel->addProvider($siteSectionProvider);

        /** @var MasterViewRenderer $renderer */
        $renderer = self::container()->get(MasterViewRenderer::class);

        // legacy page, with locale "ar", RTLLocales contains all default RTL languages by default, we SHOULD HAVE dir=rtl applied
        /** @var Page $page */
        $page = self::container()->get(SimpleTitlePage::class);
        $page->initialize();
        $result = $renderer->renderPage($page, []);
        $testHtml = new TestHtmlDocument($result);
        $testHtml->assertContainsString("dir=rtl");

        // layouts page, with locale "ar", RTLLocales contains all default RTL languages by default, we SHOULD HAVE dir=rtl applied
        /** @var LayoutPage $layoutPage */
        $layoutPage = self::container()->get(LayoutPage::class);
        $layoutPage->initialize();
        $result = $renderer->renderPage($layoutPage, []);
        $testHtml = new TestHtmlDocument($result);
        $testHtml->assertContainsString("dir=rtl");

        $this->runWithConfig(
            [
                "Garden.RTLLocales" => [],
            ],
            function () {
                /** @var MasterViewRenderer $renderer */
                $renderer = self::container()->get(MasterViewRenderer::class);

                // legacy page, with locale "ar", RTLLocales config IS empty, we SHOULD NOT HAVE dir=rtl applied
                /** @var Page $page */
                $page = self::container()->get(SimpleTitlePage::class);
                $page->initialize();
                $result = $renderer->renderPage($page, []);
                $testHtml = new TestHtmlDocument($result);
                $testHtml->assertNotContainsString("dir=rtl");

                // layouts page, with locale "ar", RTLLocales config IS empty, we SHOULD NOT HAVE dir=rtl applied
                /** @var LayoutPage $layoutPage */
                $layoutPage = self::container()->get(LayoutPage::class);
                $layoutPage->initialize();
                $result = $renderer->renderPage($layoutPage, []);
                $testHtml = new TestHtmlDocument($result);
                $testHtml->assertNotContainsString("dir=rtl");
            }
        );

        $siteSectionProvider->setCurrentSiteSection($section_english);
        $siteSectionModel->addProvider($siteSectionProvider);
        $this->runWithConfig(
            [
                "Garden.RTLLocales" => [
                    0 => "ar",
                    1 => "en",
                ],
            ],
            function () {
                /** @var MasterViewRenderer $renderer */
                $renderer = self::container()->get(MasterViewRenderer::class);

                // legacy page, with locale "en", RTLLocales has ar and en(wrong) values, we still SHOULD NOT HAVE dir=rtl applied, because 'en' is not in default RTL languages list
                /** @var Page $page */
                $page = self::container()->get(SimpleTitlePage::class);
                $page->initialize();
                $result = $renderer->renderPage($page, []);
                $testHtml = new TestHtmlDocument($result);
                $testHtml->assertNotContainsString("dir=rtl");

                // layouts page, with locale "en", RTLLocales has ar and en(wrong) values, we still SHOULD NOT HAVE dir=rtl applied, because 'en' is not in default RTL languages list
                /** @var LayoutPage $layoutPage */
                $layoutPage = self::container()->get(LayoutPage::class);
                $layoutPage->initialize();
                $result = $renderer->renderPage($layoutPage, []);
                $testHtml = new TestHtmlDocument($result);
                $testHtml->assertNotContainsString("dir=rtl");
            }
        );
    }
}
