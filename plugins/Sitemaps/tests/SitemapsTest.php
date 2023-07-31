<?php
/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\Sitemaps;

use VanillaTests\APIv2\AbstractAPIv2Test;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Tests of the Sitemap addon class.
 */
class SitemapsTest extends AbstractAPIv2Test
{
    use CommunityApiTestTrait;
    use UsersAndRolesApiTestTrait;

    protected static $addons = ["vanilla", "sitemaps"];

    /**
     * @inheritdoc
     */
    public function setUp(): void
    {
        parent::setUp();
        $config = [
            "Vanilla.Discussions.PerPage" => 2,
            "Feature.discussionSiteMaps.Enabled" => true,
        ];
        \Gdn::config()->saveToConfig($config);
        \Gdn::config()->save();
    }

    /**
     * Test index sitemap .
     *
     * @depends testPrepareDataForTest
     * @param array $categories
     * @return void
     */
    public function testSiteMapIndex(array $categories): void
    {
        $result = $this->bessy()->get("sitemapindex.xml")->Data;
        $homepageSitemap = url("sitemap-homepages.xml", true);
        $categorySiteMap = url("sitemap-categories-1-100.xml", true);
        $expectedLoc = [
            $homepageSitemap,
            $categorySiteMap,
            url("sitemap-category-" . $categories["visibleCategoryWithDiscussions"]["urlcode"] . "-1-1000.xml", true),
        ];
        $notExpectedLoc = [
            url("sitemap-category-" . $categories["protectedCategoryWithDiscussion"]["urlcode"] . "-1-1000.xml", true),
        ];

        $sitemapLoc = array_column($result["SiteMaps"], "Loc");

        foreach ($expectedLoc as $loc) {
            $this->assertContains($loc, $sitemapLoc);
        }

        foreach ($notExpectedLoc as $loc) {
            $this->assertNotContains($loc, $sitemapLoc);
        }
    }

    /**
     * Test sitemap homepage
     *
     * @return void
     */
    public function testSiteMapHomePage(): void
    {
        $result = $this->bessy()->get("sitemap-homepages.xml")->Data;

        $homePageURl = \Gdn::request()->getSimpleUrl();

        $this->assertEquals($homePageURl, $result["Urls"][0]["Loc"]);
    }

    /**
     * Test category site map
     *
     * @depends testPrepareDataForTest
     * @param array $categories
     * @return void
     */
    public function testCategorySiteMap(array $categories): void
    {
        $result = $this->bessy()->get("sitemap-categories-1-100.xml")->Data;
        $expectedCategoryUrls = [
            $categories["visibleCategoryWithDiscussions"]["url"],
            $categories["visibleCategoryWithDiscussions"]["url"] . "/p2",
            $categories["visibleCategoryWithDiscussions"]["url"] . "/p3",
        ];
        $this->assertCount(3, $result["Urls"]);
        foreach ($result["Urls"] as $key => $val) {
            $this->assertEquals($expectedCategoryUrls[$key], $val["Loc"]);
        }
    }
    /**
     * Provide necessary data required for test
     *
     * @return array
     */
    public function testPrepareDataForTest(): array
    {
        //Create a category with some discussions
        $visibleCategoryWithDiscussions = $this->createCategory([
            "name" => "Category A",
            "description" => "category with discussions",
            "parentCategoryID" => \CategoryModel::ROOT_ID,
        ]);
        $visibleCategoryWithDiscussions["discussions"] = $this->createCategoryDiscussions(
            $visibleCategoryWithDiscussions["categoryID"],
            "Discussion A",
            5
        );
        $visibleCategoryWithNoDiscussion = $this->createCategory([
            "name" => "Category B",
            "description" => "category with no discussions",
            "parentCategoryID" => \CategoryModel::ROOT_ID,
        ]);

        $protectedCategoryWithDiscussion = $this->createPermissionedCategory(
            [
                "name" => "Category C",
                "description" => "category with  discussions having view permission",
                "parentCategoryID" => \CategoryModel::ROOT_ID,
            ],
            [\RoleModel::MEMBER_ID, \RoleModel::ADMIN_ID]
        );
        $protectedCategoryWithDiscussion["discussions"] = $this->createCategoryDiscussions(
            $protectedCategoryWithDiscussion["categoryID"],
            "Discussion C",
            5
        );
        $this->assertTrue(true);
        return [
            "visibleCategoryWithDiscussions" => $visibleCategoryWithDiscussions,
            "visibleCategoryWithNoDiscussion" => $visibleCategoryWithNoDiscussion,
            "protectedCategoryWithDiscussion" => $protectedCategoryWithDiscussion,
        ];
    }

    /**
     * Create Discussion
     *
     * @param int $categoryId
     * @param string $name
     * @param int $counts
     * @return void
     */
    private function createCategoryDiscussions(int $categoryId, string $name = "Test", int $counts = 1)
    {
        $discussions = [];
        for ($i = 0; $i < $counts; $i++) {
            $discussions[] = $this->createDiscussion([
                "name" => $name . "-" . $i,
                "categoryID" => $categoryId,
            ]);
        }
        return $discussions;
    }
}
