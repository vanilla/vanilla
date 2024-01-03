<?php
/**
 * @copyright 2008-2023 Vanilla Forums, Inc.
 * @license Proprietary
 */

namespace VanillaTests\Forum\Widgets;

use VanillaTests\SiteTestCase;
use VanillaTests\Layout\LayoutTestTrait;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;

/**
 * Test CategoriesWidget.
 */
class CategoriesWidgetTest extends SiteTestCase
{
    use LayoutTestTrait;
    use CommunityApiTestTrait;

    /**
     * A helper to generate hydrate spec.
     */
    public function makeHydrateSpec(array $overrides = []): array
    {
        return $overrides + [
            '$hydrate' => "react.categories",
            "title" => "My Categories",
            "titleType" => "static",
            "descriptionType" => "none",
        ];
    }

    /**
     * A helper to generate expected result.
     */
    public function makeExpectedResult(array $overrides = [], array $propsOverrides): array
    {
        return $overrides + [
            '$reactComponent' => "CategoriesWidget",
            '$reactProps' => $propsOverrides + [
                "title" => "My Categories",
                "titleType" => "static",
                "descriptionType" => "none",
            ],
        ];
    }

    /**
     * This is a helper to generate category item.
     */
    public function makeCategoryItem($category = null, $depth = 1, $childCategory = null): array
    {
        $categoryItem = [
            "to" => $category["url"],
            "iconUrl" => $category["iconUrl"],
            "iconUrlSrcSet" => null,
            "imageUrl" => $category["bannerUrl"],
            "imageUrlSrcSet" => null,
            "name" => $category["name"],
            "description" => $category["description"],
            "counts" => [
                [
                    "labelCode" => "discussions",
                    "count" => $category["countDiscussions"],
                    "countAll" => $category["countAllDiscussions"],
                ],
                [
                    "labelCode" => "comments",
                    "count" => $category["countComments"],
                    "countAll" => $category["countAllComments"],
                ],
                [
                    "labelCode" => "posts",
                    "count" => $category["countDiscussions"] + $category["countComments"],
                    "countAll" => $category["countAllDiscussions"] + $category["countAllComments"],
                ],
                [
                    "labelCode" => "followers",
                    "count" => $category["countFollowers"],
                ],
            ],
            "categoryID" => $category["categoryID"],
            "parentCategoryID" => $category["parentCategoryID"],
            "displayAs" => $category["displayAs"],
            "depth" => $depth,
            "children" => [],
            "lastPost" => null,
            "preferences" => null,
        ];

        if ($childCategory) {
            $categoryItem["children"][] = $this->makeCategoryItem($childCategory, 3);
        }
        return $categoryItem;
    }

    /**
     * Test that we can hydrate Categories Widget.
     */
    public function testHydrateCategoriesWidget()
    {
        $this->resetTable("Category");
        $category1 = $this->createCategory(["name" => "My category 1", "urlCode" => "cat1"]);
        $category2 = $this->createCategory([
            "name" => "My category 2",
            "parentCategoryID" => "-1",
            "urlCode" => "cat2",
        ]);

        $expectedResultForBasicSpec = $this->makeExpectedResult(
            [],
            [
                "apiParams" => [
                    "filter" => "none",
                ],
                "itemData" => [$this->makeCategoryItem($category2), $this->makeCategoryItem($category1)],
            ]
        );

        // Default case when we don't specify categoryID/parentCategoryID in apiParams,
        // we should just expect the 2 categories we have
        $this->assertHydratesTo($this->makeHydrateSpec(), [], $expectedResultForBasicSpec);

        $categoryFilterApiParams = [
            "filter" => "featured",
            "categoryID" => [$category2["categoryID"]],
            "featuredCategoryID" => [$category1["categoryID"]],
        ];

        // With apiParams, itemOptions, containerOptions and categoryOptions
        $specWithCategoryFilterAndOtherOptions = $this->makeHydrateSpec([
            "apiParams" => $categoryFilterApiParams,
            "containerOptions" => [
                "borderType" => "border",
                "displayType" => "list",
            ],
            "categoryOptions" => [
                "description" => [
                    "display" => false,
                ],
            ],
            "itemOptions" => [
                "contentType" => "title-description",
            ],
            '$reactTestID' => "catwidget",
        ]);
        unset($categoryFilterApiParams["categoryID"]);
        $expectedWithCategoryFilterAndOtherOptions = [
            '$reactComponent' => "CategoriesWidget",
            '$reactProps' => [
                "title" => "My Categories",
                "titleType" => "static",
                "descriptionType" => "none",
                "apiParams" => $categoryFilterApiParams,
                "containerOptions" => $specWithCategoryFilterAndOtherOptions["containerOptions"],
                "categoryOptions" => $specWithCategoryFilterAndOtherOptions["categoryOptions"],
                "itemOptions" => $specWithCategoryFilterAndOtherOptions["itemOptions"],
                "itemData" => [$this->makeCategoryItem($category1)],
            ],
            '$reactTestID' => "catwidget",
            '$seoContent' => <<<HTML
<div class=pageBox>
    <div class=pageHeadingBox>
        <h2>My Categories</h2>
    </div>
    <ul class=linkList>
        <li><a href=https://vanilla.test/categorieswidgettest/categories/cat1>My category 1</a></li>
    </ul>
</div>
HTML
        ,
        ];
        // We expect only filtered category
        $this->assertHydratesTo($specWithCategoryFilterAndOtherOptions, [], $expectedWithCategoryFilterAndOtherOptions);

        // More categories to test if we specify parent category, we should have only top level categories under it
        $category3 = $this->createCategory(["name" => "My category 3"]);
        $category4 = $this->createCategory(["name" => "My category 4", "featured" => true]);

        $parentCategoryFilterApiParams = [
            "filter" => "category",
            "filterCategorySubType" => "set",
            "categoryID" => 2,
            "followed" => false,
        ];

        $specWithParentCategoryFilter = $this->makeHydrateSpec([
            "apiParams" => $parentCategoryFilterApiParams,
        ]);

        $expectedWithParentCategoryFilter = $this->makeExpectedResult(
            [],
            [
                "apiParams" => $parentCategoryFilterApiParams,
                "itemData" => [$this->makeCategoryItem($category3, 2, $category4)],
            ]
        );

        // Should only get category 3, which is direct child of parent category 2
        $this->assertHydratesTo($specWithParentCategoryFilter, [], $expectedWithParentCategoryFilter);
    }
}
