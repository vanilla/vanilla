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
            "limit" => 10,
            "parentCategoryID" => -1,
        ];
        $spec = [
            '$hydrate' => "react.categories",
            "title" => "My Categories",
        ];
        //only 1 category
        $apiParams = [
            "categoryID" => $category1["categoryID"],
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
                "apiParams" => $defaultApiParams,
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
                "containerOptions" => $spec2["containerOptions"],
                "itemOptions" => $spec2["itemOptions"],
                "apiParams" => $apiParams,
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
    }
}
