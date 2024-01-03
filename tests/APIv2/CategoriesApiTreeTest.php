<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\SiteTestCase;

/**
 *
 */
class CategoriesApiTreeTest extends SiteTestCase
{
    use CommunityApiTestTrait;

    /**
     * Test categories API tree output formatting.
     */
    public function testCategoryApiTree()
    {
        $cat1 = $this->createCategory(["name" => "cat1", "parentCategoryID" => -1]);
        $cat1_1 = $this->createCategory(["name" => "cat1.1", "parentCategoryID" => $cat1["categoryID"], "sort" => 1]);
        $cat1_1_1 = $this->createCategory(["name" => "cat1.1.1", "parentCategoryID" => $cat1_1["categoryID"]]);
        $cat1_1_1_1 = $this->createCategory(["name" => "cat1.1.1.1", "parentCategoryID" => $cat1_1_1["categoryID"]]);
        $cat1_2 = $this->createCategory(["name" => "cat1.2", "parentCategoryID" => $cat1["categoryID"], "sort" => 2]);
        $cat2 = $this->createCategory(["name" => "cat2", "parentCategoryID" => -1]);
        $this->sortCategories([$cat1["categoryID"] => [$cat1_1["categoryID"], $cat1_2["categoryID"]]]);

        $stripCategory = function (array $cat) use (&$stripCategory) {
            return [
                "name" => $cat["name"],
                "children" => array_map($stripCategory, $cat["children"] ?? []),
            ];
        };

        $result = $this->api()
            ->get("/categories", ["outputFormat" => "tree", "maxDepth" => 2, "parentCategoryID" => $cat1["categoryID"]])
            ->getBody();

        $this->assertEquals(
            [
                [
                    "name" => "cat1.1",
                    "children" => [
                        [
                            "name" => "cat1.1.1",
                            "children" => [],
                        ],
                    ],
                ],
                [
                    "name" => "cat1.2",
                    "children" => [],
                ],
            ],
            array_map($stripCategory, $result)
        );
    }
}
