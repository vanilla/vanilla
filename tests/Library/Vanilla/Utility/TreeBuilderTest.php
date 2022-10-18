<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Utility;

use Vanilla\Utility\TreeBuilder;
use VanillaTests\VanillaTestCase;

/**
 * Tests for TreeBuilder.
 */
class TreeBuilderTest extends VanillaTestCase
{
    /**
     * Test that we can create a tree.
     */
    public function testBuildTree()
    {
        $input = [
            $this->sourceItem("1", 1, null),
            $this->sourceItem("1.1", 2, 1),
            $this->sourceItem("1.1.1", 3, 2),
            $this->sourceItem("1.2", 4, 1),
            $this->sourceItem("recurse1", 5, 6),
            $this->sourceItem("recurse2", 6, 5),
            $this->sourceItem("2", 100, -1),
        ];

        $expected = [
            $this->resultItem("1", 1, null, [
                $this->resultItem("1.1", 2, 1, [$this->resultItem("1.1.1", 3, 2)]),
                $this->resultItem("1.2", 4, 1),
            ]),
            $this->resultItem("recurse1", 5, 6, [$this->resultItem("recurse2", 6, 5)]),
            $this->resultItem("2", 100, -1),
        ];

        $builder = TreeBuilder::create("recordID", "parentID")
            ->setChildrenFieldName("childs")
            ->setAllowUnreachableNodes(true);
        $actual = $builder->buildTree($input);

        $this->assertSame($expected, $actual);
    }

    /**
     * Test that building trees can ignore unreachable nodes.
     */
    public function testBuildTreeNoUnreachable()
    {
        $input = [
            $this->sourceItem("1", 1, -1),
            $this->sourceItem("1.1", 2, 1),
            $this->sourceItem("recurse1", 5, 6),
            $this->sourceItem("recurse2", 6, 5),
            $this->sourceItem("nowhere", 100, 500),
        ];

        $expected = [$this->resultItem("1", 1, -1, [$this->resultItem("1.1", 2, 1)])];

        $builder = TreeBuilder::create("recordID", "parentID")
            ->setChildrenFieldName("childs")
            ->setAllowUnreachableNodes(false)
            ->setRootID(-1);
        $actual = $builder->buildTree($input);
        $this->assertSame($expected, $actual);
    }

    /**
     * Test that building trees can ignore unreachable nodes.
     */
    public function testBuildTreeWrongRootID()
    {
        $input = [
            $this->sourceItem("1.1", 2, 1),
            $this->sourceItem("1.1.1", 5, 2),
            $this->sourceItem("nowhere", 100, 500),
        ];

        $expected = [
            $this->resultItem("1.1", 2, 1, [$this->resultItem("1.1.1", 5, 2)]),
            $this->resultItem("nowhere", 100, 500),
        ];

        $builder = TreeBuilder::create("recordID", "parentID")
            ->setChildrenFieldName("childs")
            ->setAllowUnreachableNodes(true)
            ->setRootID(-1);
        $actual = $builder->buildTree($input);
        $this->assertSame($expected, $actual);
    }

    /**
     * Test flattening of a tree.
     */
    public function testFlattenTree()
    {
        $input = [
            $this->resultItem("item1", 1, -1, [
                $this->resultItem("item1.1", 2, 1, [$this->resultItem("item1.1.1", 4, 2)]),
                $this->resultItem("item1.2", 3, 1),
            ]),
            $this->resultItem("item2", 5, -1),
        ];

        $expected = [
            $this->sourceItem("item1", 1, -1),
            $this->sourceItem("item1.1", 2, 1),
            $this->sourceItem("item1.1.1", 4, 2),
            $this->sourceItem("item1.2", 3, 1),
            $this->sourceItem("item2", 5, -1),
        ];

        $actual = TreeBuilder::create("recordID", "parentID")
            ->setChildrenFieldName("childs")
            ->flattenTree($input);

        $this->assertEquals($expected, $actual);
    }

    /**
     * Test sorting.
     */
    public function testSort()
    {
        $input = [
            $this->sourceItem("item1.2", 3, 1),
            $this->sourceItem("item2", 5, -1),
            $this->sourceItem("item1.1", 2, 1),
            $this->sourceItem("item1", 1, -1),
            $this->sourceItem("root", -1, null),
            $this->sourceItem("item1.1.1", 4, 2),
        ];

        $expected = [
            $this->sourceItem("root", -1, null),
            $this->sourceItem("item1", 1, -1),
            $this->sourceItem("item1.1", 2, 1),
            $this->sourceItem("item1.1.1", 4, 2),
            $this->sourceItem("item1.2", 3, 1),
            $this->sourceItem("item2", 5, -1),
        ];

        $actual = TreeBuilder::create("recordID", "parentID")
            ->setChildrenFieldName("childs")
            ->setSorter(function ($a, $b) {
                return $a["name"] <=> $b["name"];
            })
            ->sort($input);
        $this->assertEquals($expected, $actual);
    }

    /**
     * Test `TreeBuilder`'s `buildTree()` function.
     */
    public function testBuildTreeFunction()
    {
        $builder = TreeBuilder::create("categoryID", "parentCategoryID")
            ->setAllowUnreachableNodes(true)
            ->setRootID(null)
            ->setChildrenFieldName("children")
            ->setSorter(function (array $catA, array $catB) {
                return ($catA["sort"] ?? 0) <=> ($catB["sort"] ?? 0);
            });

        $desiredTreeStructure = [
            [
                "categoryID" => 3,
                "parentCategoryID" => 2,
                "children" => [
                    [
                        "categoryID" => 1,
                        "parentCategoryID" => 3,
                        "children" => [],
                    ],
                ],
            ],
        ];

        $categoriesA = [["categoryID" => 1, "parentCategoryID" => 3], ["categoryID" => 3, "parentCategoryID" => 2]];
        $treeA = $builder->setRootID(2)->buildTree($categoriesA);

        $categoriesB = [["categoryID" => 3, "parentCategoryID" => 2], ["categoryID" => 1, "parentCategoryID" => 3]];
        $treeB = $builder->setRootID(2)->buildTree($categoriesB);

        // No matter the order of the tree elements, the generated tree should be the same.
        $this->assertEquals($treeA, $treeB);

        // The both should correspond to the desired tree structure.
        $this->assertEquals($desiredTreeStructure, $treeA);
        $this->assertEquals($desiredTreeStructure, $treeB);
    }

    /**
     * Create a source item.
     *
     * @param string $name
     * @param int $recordID
     * @param int|null $parentID
     *
     * @return array
     */
    private function sourceItem(string $name, int $recordID, ?int $parentID): array
    {
        return [
            "recordID" => $recordID,
            "parentID" => $parentID,
            "name" => $name,
        ];
    }

    /**
     * Create a result item.
     *
     * @param string $name
     * @param int $recordID
     * @param int|null $parentID
     * @param array $children
     *
     * @return array
     */
    private function resultItem(string $name, int $recordID, ?int $parentID, array $children = []): array
    {
        $result = $this->sourceItem($name, $recordID, $parentID);
        $result["childs"] = $children;
        return $result;
    }
}
