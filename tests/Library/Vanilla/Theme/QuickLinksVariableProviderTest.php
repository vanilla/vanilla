<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Theme;

use Vanilla\Theme\VariableProviders\QuickLinksVariableProvider;
use VanillaTests\Fixtures\QuickLinks\MockQuickLinkProviderInterface1;
use VanillaTests\Fixtures\QuickLinks\MockQuickLinkProviderInterface2;
use VanillaTests\Fixtures\QuickLinks\MockQuickLinkProviderInterface3;
use VanillaTests\Fixtures\QuickLinks\MockQuickLinksVariableProvider;
use VanillaTests\Fixtures\QuickLinks\QuickLinksVariables;
use VanillaTests\SiteTestCase;

/**
 * Class QuickLinksVariableProviderTest
 *
 * @package VanillaTests\Library\Vanilla\Theme
 */
class QuickLinksVariableProviderTest extends SiteTestCase {

    /** @var QuickLinksVariableProvider $quickLinksProviderInterface */
    private $quickLinksVariableProvider;

    /**
     * Setup.
     */
    public static function setUpBeforeClass(): void {
        self::$addons = ['vanilla'];
        parent::setUpBeforeClass();
    }

    /**
     * Setup.
     */
    public function setUp(): void {
        parent::setUp();
        $this->quickLinksVariableProvider = self::container()->get(QuickLinksVariableProvider::class);
    }

    /**
     * Test adding Link providers.
     */
    public function testAddProviders() {
        $this->quickLinksVariableProvider->resetProviders();

        $this->quickLinksVariableProvider->addQuickLinkProvider(
            new MockQuickLinkProviderInterface1()
        );
        $this->quickLinksVariableProvider->addQuickLinkProvider(
            new MockQuickLinkProviderInterface2()
        );
        $this->quickLinksVariableProvider->addQuickLinkProvider(
            new MockQuickLinkProviderInterface3()
        );

        $this->assertEquals(3, count($this->quickLinksVariableProvider->getAllProviders()));
    }

    /**
     * Test getAllLinks
     */
    public function testGetAllLinks() {
        $this->testAddProviders();
        $providerLinks = $this->quickLinksVariableProvider->getAllLinks();
        $expectedLinkIDs = $this->allLinkIDDataProvider();

        $linkData = [];
        foreach ($providerLinks as $providerLinkProperties) {
            $linkData[] = $providerLinkProperties->getID();
        }

        $this->assertEquals($expectedLinkIDs, $linkData);
    }

    /**
     * Test getVariables.
     */
    public function testGetVariables() {
        $this->testAddProviders();
        $variables = $this->quickLinksVariableProvider->getVariables();

        $expectedCounts = $this->countsDataProvider();
        $this->assertArrayHasKey('counts', $variables['quickLinks']);
        $this->assertEquals($expectedCounts, $variables['quickLinks']['counts']);
    }

    /**
     * Test getVariables.
     */
    public function testGetDefaults() {
        $this->testAddProviders();
        $variables = $this->quickLinksVariableProvider->getVariableDefaults();
        $expectedLinkIDs = $this->allLinkIDDataProvider();

        $this->assertArrayHasKey('quickLinks', $variables);
        $this->assertArrayHasKey('links', $variables['quickLinks']);

        $linkData = [];
        foreach ($variables['quickLinks']['links'] as $link) {
            $linkData[] = $link->getID();
        }
        $this->assertEquals($expectedLinkIDs, $linkData);
    }


    /**
     * LinkIDs data provider.
     *
     * @return array
     */
    protected function allLinkIDDataProvider() {
        return [
            'mock-quick-link-1',
            'mock-quick-link-2',
            'mock-quick-link-3',
        ];
    }

    /**
     * Counts data provider.
     *
     * @return array
     */
    protected function countsDataProvider() {
        return [
            'mock-quick-link-1' => null,
            'mock-quick-link-2' => 4,
            'mock-quick-link-3' => 5,
        ];
    }
}
