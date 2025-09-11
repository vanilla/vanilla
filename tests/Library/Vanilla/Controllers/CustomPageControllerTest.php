<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\Library\Vanilla\Controllers;

use Garden\Web\Dispatcher;
use Vanilla\Models\CustomPageModel;
use Vanilla\Site\SiteSectionModel;
use VanillaTests\Dashboard\Utils\CustomPagesApiTestTrait;
use VanillaTests\EventSpyTestTrait;
use VanillaTests\Fixtures\Html\TestHtmlDocument;
use VanillaTests\Fixtures\MockSiteSection;
use VanillaTests\Fixtures\MockSiteSectionProvider;
use VanillaTests\Fixtures\Request;
use VanillaTests\SiteTestCase;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Tests for looking up and dispatching the controller to serve custom pages.
 */
class CustomPageControllerTest extends SiteTestCase
{
    use CustomPagesApiTestTrait, EventSpyTestTrait, UsersAndRolesApiTestTrait;

    protected Dispatcher $dispatcher;

    protected MockSiteSectionProvider $mockSiteSectionProvider;

    protected SiteSectionModel $siteSectionModel;

    /**
     * @inheritdoc
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->dispatcher = self::container()->get(Dispatcher::class);
        $this->mockSiteSectionProvider = self::container()->get(MockSiteSectionProvider::class);
        $this->siteSectionModel = self::container()->get(SiteSectionModel::class);
    }

    /**
     * @inheritdoc
     */
    public function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * Tests dispatching custom pages by creating two custom pages and checking
     * that the dispatcher serves the correct page for the matching url path.
     *
     * @return void
     * @throws \Exception
     */
    public function testDispatchCustomPage()
    {
        $mockSiteSection = new MockSiteSection("test", "en", "/actual-section", "custom-site-section-id", "test1");
        $this->mockSiteSectionProvider->addSiteSections([$mockSiteSection]);

        $this->createCustomPage([
            "urlcode" => "/custom-page-dispatch-test",
            "seoTitle" => "default-site-section-title",
        ]);
        $this->createCustomPage([
            "urlcode" => "/custom-page-dispatch-test",
            "siteSectionID" => "custom-site-section-id",
            "seoTitle" => "custom-site-section-title",
        ]);

        $this->siteSectionModel->setCurrentSiteSection($mockSiteSection);
        $data = $this->dispatcher->dispatch(new Request("/custom-page-dispatch-test"));
        $this->siteSectionModel->resetCurrentSiteSection();

        $this->assertSame("text/html; charset=utf-8", $data->getHeader("Content-Type"));
        $responseHtml = new TestHtmlDocument($data->getData());
        $responseHtml->assertCssSelectorText("head title", "custom-site-section-title - CustomPageControllerTest");
    }

    /**
     * Check that error response (such as when user doesn't have access to a page) comes back as HTML.
     *
     * @return void
     * @throws \Exception
     */
    public function testDispatchCustomPageWithErrorReturnsHtml()
    {
        $customPage = $this->createCustomPage(["roleIDs" => [\RoleModel::MOD_ID]]);
        $user = $this->createUser();

        $response = $this->runWithUser(fn() => $this->dispatcher->dispatch(new Request($customPage["urlcode"])), $user);

        $this->assertSame("text/html; charset=utf-8", $response->getHeader("Content-Type"));

        // Look for SEO error text.
        $responseHtml = new TestHtmlDocument($response->getData());
        $responseHtml->assertCssSelectorText("h1", "404 - Page not found.");
    }
}
