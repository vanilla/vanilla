<?php
/**
 * @copyright 2008-2022 Vanilla Forums, Inc.
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
    use LayoutTestTrait, CommunityApiTestTrait;

    /**
     * Test that we can hydrate Categories Widget.
     */
    public function testHydrateCategoriesWidget()
    {
        $this->resetTable("Category");
        $category1 = $this->createCategory(["name" => "My category 1"]);
        $category2 = $this->createCategory(["name" => "My category 2", "parentCategoryID" => "-1"]);

        //the case when we don't specify limit or categoryID/parentCategoryID in apiParams
        $defaultApiParams = [
            "filter" => "none",
            "limit" => 10,
        ];
        $spec = [
            '$hydrate' => "react.categories",
            "title" => "My Categories",
        ];
        //only 1 category
        $apiParams = [
            "filter" => "category",
            "categoryID" => [$category1["categoryID"]],
            "limit" => 3,
        ];

        //with apiParams, itemOptions and containerOptions
        $spec2 = [
            '$hydrate' => "react.categories",
            "title" => "My Categories",
            "apiParams" => $apiParams,
            "containerOptions" => [
                "borderType" => "border",
                "displayType" => "list",
            ],
            "itemOptions" => [
                "contentType" => "title-description",
            ],
        ];

        $expected = [
            '$reactComponent' => "CategoriesWidget",
            '$reactProps' => [
                "title" => "My Categories",
                "apiParams" => array_merge($defaultApiParams),
                "itemData" => [
                    [
                        "to" => $category2["url"],
                        "iconUrl" => $category2["iconUrl"],
                        "iconUrlSrcSet" => null,
                        "imageUrl" => $category2["bannerUrl"],
                        "imageUrlSrcSet" => null,
                        "name" => $category2["name"],
                        "description" => $category2["description"],
                        "counts" => [
                            [
                                "labelCode" => "discussions",
                                "count" => $category2["countAllDiscussions"],
                            ],
                        ],
                    ],
                    [
                        "to" => $category1["url"],
                        "iconUrl" => $category1["iconUrl"],
                        "iconUrlSrcSet" => null,
                        "imageUrl" => $category1["bannerUrl"],
                        "imageUrlSrcSet" => null,
                        "name" => $category1["name"],
                        "description" => $category1["description"],
                        "counts" => [
                            [
                                "labelCode" => "discussions",
                                "count" => $category1["countAllDiscussions"],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $expected2 = [
            '$reactComponent' => "CategoriesWidget",
            '$reactProps' => [
                "title" => "My Categories",
                "apiParams" => $apiParams,
                "containerOptions" => $spec2["containerOptions"],
                "itemOptions" => $spec2["itemOptions"],
                "itemData" => [
                    [
                        "to" => $category1["url"],
                        "iconUrl" => $category1["iconUrl"],
                        "iconUrlSrcSet" => null,
                        "imageUrl" => $category1["bannerUrl"],
                        "imageUrlSrcSet" => null,
                        "name" => $category1["name"],
                        "description" => $category1["description"],
                        "counts" => [
                            [
                                "labelCode" => "discussions",
                                "count" => $category1["countAllDiscussions"],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $this->assertHydratesTo($spec, [], $expected);
        $this->assertHydratesTo($spec2, [], $expected2);

        //more categories to test if we specify parent category, we should have only top level categories under it
        $category3 = $this->createCategory(["name" => "My category 3"]);
        $category4 = $this->createCategory(["name" => "My category 4"]);

        $parentCategoryApiParams = [
            "filter" => "parentCategory",
            "parentCategoryID" => 2,
        ];

        $spec3 = [
            '$hydrate' => "react.categories",
            "title" => "My Categories",
            "apiParams" => $parentCategoryApiParams,
        ];

        $expected3 = [
            '$reactComponent' => "CategoriesWidget",
            '$reactProps' => [
                "title" => "My Categories",
                "apiParams" => array_merge($parentCategoryApiParams, ["limit" => 10]),
                "itemData" => [
                    [
                        "to" => $category3["url"],
                        "iconUrl" => $category3["iconUrl"],
                        "iconUrlSrcSet" => null,
                        "imageUrl" => $category3["bannerUrl"],
                        "imageUrlSrcSet" => null,
                        "name" => $category3["name"],
                        "description" => $category3["description"],
                        "counts" => [
                            [
                                "labelCode" => "discussions",
                                "count" => $category3["countAllDiscussions"],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        //should only get category 3, which is direct child of parent category 2
        $this->assertHydratesTo($spec3, [], $expected3);
    }
}
