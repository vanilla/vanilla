<?php
/**
 * @author Raphaël Bergina <raphael.bergina@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core;

use Vanilla\Site\DefaultSiteSection;
use Vanilla\Site\SiteSectionModel;
use VanillaTests\Fixtures\MockConfig;
use VanillaTests\Fixtures\MockSiteSectionProvider;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\SiteTestCase;

/**
 * Class Gdn_ThemeTest
 *
 * @package VanillaTests\Library\Core
 */
class Gdn_ThemeTest extends SiteTestCase
{
    use CommunityApiTestTrait;

    /**
     * @var MockSiteSectionProvider
     */
    protected $siteSectionProvider;

    /**
     * Set site section provider.
     */
    public function setUp(): void
    {
        parent::setUp();
        /** @var MockSiteSectionProvider $siteSectionProvider */
        $this->siteSectionProvider = self::container()->get(MockSiteSectionProvider::class);
        /** @var SiteSectionModel $siteSectionModel */
        $siteSectionModel = self::container()->get(SiteSectionModel::class);
        $siteSectionModel->resetCurrentSiteSection();
    }

    /**
     * Data for breadcrumbs with discussions as homepage.
     *
     * @return array[]
     */
    public function breadcrumbsProviderDiscussions(): array
    {
        return [
            "with discussions as defaultController  on discussions page" => [
                "defaultController" => "discussions",
                "on" => [
                    "page" => "/discussions",
                    "breadcrumbs" => ["Home"],
                ],
            ],
            "with discussions as defaultController  on categories page" => [
                "defaultController" => "discussions",
                "on" => [
                    "page" => "/categories",
                    "breadcrumbs" => ["Home"],
                ],
            ],
            "with discussions as defaultController  on sub categories page" => [
                "defaultController" => "discussions",
                "on" => [
                    "page" => "/categories/{category2.urlcode}",
                    "breadcrumbs" => ["Home", "{category1.name}", "{category2.name}"],
                ],
            ],
        ];
    }

    /**
     * Data for breadcrumbs with categories as homepage.
     *
     * @return array[]
     */
    public function breadcrumbsProviderCategories(): array
    {
        return [
            "with categories as defaultController  on discussions page" => [
                "defaultController" => "categories",
                "on" => [
                    "page" => "/discussions",
                    "breadcrumbs" => ["Home", "Recent Discussions"],
                ],
            ],
            "with categories as defaultController  on categories page" => [
                "defaultController" => "categories",
                "on" => [
                    "page" => "/categories",
                    "breadcrumbs" => ["Home"],
                ],
            ],
            "with categories as defaultController  on sub categories page" => [
                "defaultController" => "categories",
                "on" => [
                    "page" => "/categories/{category2.urlcode}",
                    "breadcrumbs" => ["Home", "{category1.name}", "{category2.name}"],
                ],
            ],
        ];
    }

    /**
     * Data for breadcrumbs testing.
     *
     * @return array[]
     */
    public function breadcrumbsProviderHome(): array
    {
        return [
            "with discussions as defaultController  on home page" => [
                "defaultController" => "discussions",
                "on" => [
                    "page" => "/",
                    "breadcrumbs" => [],
                ],
            ],
            "with categories as defaultController  on home page" => [
                "defaultController" => "categories",
                "on" => [
                    "page" => "/",
                    "breadcrumbs" => [],
                ],
            ],
        ];
    }

    /**
     * The breadcrumbs final links.
     *
     * @param string $defaultController
     * @param array $items
     * @dataProvider breadcrumbsProviderDiscussions
     * @dataProvider breadcrumbsProviderCategories
     */
    public function testBreadcrumbsOnPage(string $defaultController, array $items): void
    {
        $category1 = $this->createCategory();
        $category2 = $this->createCategory(["parentCategoryID" => $category1["categoryID"]]);
        /** @var \Gdn_Router $router */
        $router = self::container()->get(\Gdn_Router::class);
        $defaultSection = new DefaultSiteSection(
            new MockConfig([
                "Routes.DefaultController" => [$defaultController, "Internal"],
            ]),
            $router
        );
        $this->siteSectionProvider->setCurrentSiteSection($defaultSection);
        $values = [
            "{category1.name}" => $category1["name"],
            "{category1.urlcode}" => $category1["urlcode"],
            "{category2.name}" => $category2["name"],
            "{category2.urlcode}" => $category2["urlcode"],
        ];
        $page = str_replace(array_keys($values), array_values($values), $items["page"]);
        $nodes = $this->bessy()
            ->getHtml($page, [], ["deliveryType" => DELIVERY_TYPE_ALL])
            ->queryCssSelector(".Breadcrumbs .CrumbLabel");
        $this->assertEquals(count($items["breadcrumbs"]), $nodes->length);
        foreach ($items["breadcrumbs"] as $key => $crumbLabel) {
            $crumbLabel = str_replace(array_keys($values), array_values($values), $crumbLabel);
            $this->assertEquals($crumbLabel, $nodes->item($key)->nodeValue);
        }
    }

    /**
     * The breadcrumbs final links on homepage.
     *
     * @param string $defaultController
     * @param array $items
     * @dataProvider breadcrumbsProviderHome
     */
    public function testBreadcrumbsOnHomepage(string $defaultController, array $items): void
    {
        /** @var \Gdn_Router $router */
        $router = self::container()->get(\Gdn_Router::class);
        $defaultSection = new DefaultSiteSection(
            new MockConfig([
                "Routes.DefaultController" => [$defaultController, "Internal"],
            ]),
            $router
        );
        $this->siteSectionProvider->setCurrentSiteSection($defaultSection);
        $this->bessy()
            ->getHtml($items["page"], [], ["deliveryType" => DELIVERY_TYPE_ALL])
            ->assertCssSelectorNotExists(".Breadcrumbs");
    }
}
