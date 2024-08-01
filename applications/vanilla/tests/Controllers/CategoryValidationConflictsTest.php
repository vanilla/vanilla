<?php
/**
 * @author Alexander Kim <alexander.k@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Forum\Controllers;

use Garden\Web\Exception\ClientException;
use Garden\Web\Exception\ServerException;
use VanillaTests\APIv2\AbstractAPIv2Test;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;

/**
 * Class CategoryValidationConflictsTest
 */
class CategoryValidationConflictsTest extends AbstractAPIv2Test
{
    use CommunityApiTestTrait;

    public static $data = [];

    /**
     * Test get categories api index conflicting params: featured, followed, categoryID etc
     *
     * @depends testPrepareCategories
     */
    public function testCategories()
    {
        /**
         * All data providers are executed before both the call to the setUpBeforeClass() static method
         * and the first call to the setUp() method.
         * Because of that you can’t access any variables you create there from within a data provider.
         * This is required in order for PHPUnit to be able to compute the total number of tests.
         */
        $provider = [
            "No params" => [[], ["1"], null],
            "categoryID" => [["categoryID" => self::$data["1"]["categoryID"]], ["1"], null],
            "featured" => [["featured" => true], ["1.2"], null],
            "followed" => [["followed" => true], ["1.1"], null],
            "archived" => [["archived" => true], [], null],
            "tree & limit" => [
                [
                    "limit" => 10,
                    "outputFormat" => "tree",
                ],
                null,
                ServerException::class,
            ],
            "tree & page" => [
                [
                    "page" => 1,
                    "outputFormat" => "tree",
                ],
                null,
                ServerException::class,
            ],
            "flat & depth" => [
                [
                    "outputFormat" => "flat",
                    "maxDepth" => 2,
                ],
                null,
                ServerException::class,
            ],
            "depth & limit" => [
                [
                    "depth" => 1,
                    "limit" => 1,
                ],
                null,
                ServerException::class,
            ],
            "depth & page" => [
                [
                    "depth" => 1,
                    "page" => 1,
                ],
                null,
                ServerException::class,
            ],
        ];

        foreach ($provider as $key => $item) {
            $params = $item[0];
            $results = $item[1];
            $exception = $item[2];
            if ($results !== null) {
                $categories = $this->api()
                    ->get("/categories", $params)
                    ->getBody();
                $this->assertEquals(count($results), count($categories), "[$key] assertEquals failed");
                foreach ($results as $key) {
                    $found = false;
                    foreach ($categories as $category) {
                        if ($category["categoryID"] === self::$data[$key]["categoryID"]) {
                            $found = true;
                            break;
                        }
                    }
                    $this->assertTrue(
                        $found,
                        "[$key] Expected category: " . self::$data[$key]["categoryID"] . " not found in api result set."
                    );
                }
            } else {
                $this->expectException($exception);
                $this->api()
                    ->get("/categories", $params)
                    ->getBody();
            }
        }
    }

    /**
     * Data provider for testCategories
     *
     * @return array
     */
    public function categoriesProvider(): array
    {
        return [
            "No params" => [[], ["1"], null],
            "categoryID" => [["categoryID" => self::$data["1"]["categoryID"]], ["1"], null],
            "featured" => [["featured" => true], ["1.2"], null],
            "followed" => [["followed" => true], ["1.1"], null],
            "archived" => [["archived" => true], [], null],
            "categoryID & featured" => [
                [
                    "categoryID" => 100,
                    "featured" => true,
                ],
                null,
                ClientException::class,
            ],
            "categoryID & followed" => [
                [
                    "categoryID" => 100,
                    "followed" => true,
                ],
                null,
                ClientException::class,
            ],
            "categoryID & archived" => [
                [
                    "categoryID" => 100,
                    "archived" => false,
                ],
                null,
                ClientException::class,
            ],
            "featured & followed" => [
                [
                    "followed" => true,
                    "featured" => true,
                ],
                null,
                ClientException::class,
            ],
        ];
    }

    /**
     * Prepare categories.
     */
    public function testPrepareCategories()
    {
        $this->resetTable("Category");
        self::$data["1"] = $cat1 = $this->createCategory([
            "parentCategoryID" => -1,
            "name" => "Cat 1",
        ]);

        self::$data["1.1"] = $cat1_1 = $this->createCategory([
            "parentCategoryID" => $cat1["categoryID"],
            "name" => "Cat 1.1",
            "followed" => true,
        ]);
        $this->api()->put("/categories/" . $cat1_1["categoryID"] . "/follow", ["followed" => true]);

        self::$data["1.2"] = $cat1_2 = $this->createCategory([
            "parentCategoryID" => $cat1["categoryID"],
            "name" => "Cat 1.2",
            "featured" => true,
        ]);

        self::$data["1.3"] = $cat1_3 = $this->createCategory([
            "parentCategoryID" => $cat1["categoryID"],
            "name" => "Cat 1.3",
        ]);

        $categories = $this->api()
            ->get("/categories")
            ->getBody();
        $this->assertEquals(1, count($categories));
    }
}
