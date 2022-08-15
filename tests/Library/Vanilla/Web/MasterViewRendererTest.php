<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Web;

use Vanilla\Web\Events\PageRenderBeforeEvent;
use Vanilla\Web\MasterViewRenderer;
use Vanilla\Web\Page;
use Vanilla\Web\SimpleTitlePage;
use VanillaTests\BootstrapTestCase;
use VanillaTests\EventSpyTestTrait;
use VanillaTests\Fixtures\Html\TestHtmlDocument;
use VanillaTests\SiteTestCase;

/**
 * Tests for the master view renderer.
 */
class MasterViewRendererTest extends SiteTestCase
{
    use EventSpyTestTrait;

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
}
