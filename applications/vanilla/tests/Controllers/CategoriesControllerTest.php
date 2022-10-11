<?php
/**
 * @author Isis Graziatto <isis.g@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Forum\Controllers;

use Garden\Web\Exception\ResponseException;
use Gdn;
use PHPUnit\Framework\TestCase;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\Models\TestCategoryModelTrait;
use VanillaTests\SetupTraitsTrait;
use VanillaTests\SiteTestTrait;
use VanillaTests\VanillaTestCase;

/**
 * Class CategoriesControllerTest
 * @package VanillaTests\Forum\Controllers
 */
class CategoriesControllerTest extends TestCase
{
    use SiteTestTrait, SetupTraitsTrait, CommunityApiTestTrait, TestCategoryModelTrait;

    /**
     * {@inheritDoc}
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->setupTestTraits();
        /** @var \Gdn_Configuration $config */
        $config = $this->container()->get(\Gdn_Configuration::class);
        $config->saveToConfig("Vanilla.Categories.Use", true);
        $this->category = $this->insertCategories(2)[0];
    }

    /**
     * {@inheritDoc}
     */
    public function tearDown(): void
    {
        parent::tearDown();
        $this->tearDownTestTraits();
        $this->tearDownTestCategoryModel();
    }

    /**
     * Test get /categories
     *
     * @return array
     */
    public function testCategoriesIndex(): array
    {
        $data = $this->bessy()->get("/categories")->Data;
        $this->assertNotEmpty($data["CategoryTree"]);

        return $data;
    }

    /**
     * Test get /categories with following filter
     *
     * @return array
     */
    public function testFollowedCategoriesIndex(): array
    {
        /** @var \Gdn_Configuration $config */
        $config = static::container()->get("Config");
        $config->set(\CategoryModel::CONF_CATEGORY_FOLLOWING, true, true, false);
        // follow a category
        $this->followCategory(\Gdn::session()->UserID, 1, true);

        $data = $this->bessy()->get(
            "/categories?followed=1&save=1&TransientKey=" . \Gdn::request()->get("TransientKey", "")
        )->Data;
        $this->assertEquals(1, count($data["CategoryTree"]));

        return $data;
    }

    /**
     * Test get /categories/sub-category with following filter
     * Category following should not affect the category tree when in a sub-category
     */
    public function testFollowedSubCategoriesIndex()
    {
        $this->insertCategories(2, ["ParentCategoryID" => 1]);

        // trigger following filter
        $this->testFollowedCategoriesIndex();

        $data = $this->bessy()->get("/categories/general")->Data;
        $this->assertEquals(2, count($data["CategoryTree"]));
    }

    /**
     * Test most recent discussion join with permissions.
     *
     * @param string $role
     * @param string $prefix
     * @param mixed $expected
     * @dataProvider providerMostRecentDataProvider
     */
    public function testMostRecentWithPermissions(string $role, string $prefix, $expected)
    {
        $this->createUserFixtures($prefix);
        $this->resetTable("Category");
        $this->resetTable("Discussion");
        $this->resetTable("Comment");
        $parentCategory = $this->createCategory(["name" => "join recent test"]);
        $publicChildCategory = $this->createCategory([
            "parentCategoryID" => $parentCategory["categoryID"],
            "name" => "public",
        ]);
        $adminChildCategory = $this->createPermissionedCategory(
            ["parentCategoryID" => $parentCategory["categoryID"], "name" => "private"],
            [16]
        );

        $user = $expected["userID"] === "apiUser" ? $this->api()->getUserID() : null;
        $discussionInPublic = $this->createDiscussion([
            "categoryID" => $publicChildCategory["categoryID"],
            "name" => "publicDiscussion",
        ]);
        $discussionInPrivate = $this->createDiscussion([
            "categoryID" => $adminChildCategory["categoryID"],
            "name" => "privateDiscussion",
        ]);
        $id = $role === "member" ? $this->memberID : $this->adminID;

        $this->getSession()->start($id);
        $data = $this->bessy()
            ->get("/categories")
            ->data("CategoryTree");
        $category = $data[0];
        $this->assertEquals($expected["title"], $category["LastTitle"]);
        $this->assertEquals($user, $category["LastUserID"]);
    }

    /**
     * Test user redirections upon marking a category as `read`.
     * A first level category would redirect to the list of categories, while nested categories would return to their
     * parent's category url (There is no category nesting within the URL).
     */
    public function testMarkReadRedirections(): void
    {
        /** @var \CategoryController $categoryController*/
        $categoryController = Gdn::getContainer()->get(\CategoryController::class);
        $transientKey = Gdn::session()->transientKey();

        $lvl1Category = $this->createCategory();
        $lvl2Category = $this->createCategory(["parentCategoryID" => $lvl1Category["categoryID"]]);
        $lvl3Category = $this->createCategory(["parentCategoryID" => $lvl2Category["categoryID"]]);

        // Testing redirection upon markRead() on $lvl1Category.
        try {
            $categoryController->markRead($lvl1Category["categoryID"], $transientKey);
        } catch (\Throwable $exception) {
            $exResponse = $exception->getResponse();
            $this->assertEquals(302, $exResponse->getStatus());
            $this->assertStringEndsWith("/categories", $exResponse->getMeta("HTTP_LOCATION"));
        }

        // Testing redirection upon markRead() on $lvl2Category.
        try {
            $categoryController->markRead($lvl2Category["categoryID"], $transientKey);
        } catch (\Throwable $exception) {
            $exResponse = $exception->getResponse();
            $this->assertEquals(302, $exResponse->getStatus());
            $this->assertEquals($lvl1Category["url"], $exResponse->getMeta("HTTP_LOCATION"));
        }

        // Testing redirection upon markRead() on $lvl3Category.
        try {
            $categoryController->markRead($lvl3Category["categoryID"], $transientKey);
        } catch (\Throwable $exception) {
            $exResponse = $exception->getResponse();
            $this->assertEquals(302, $exResponse->getStatus());
            $this->assertEquals($lvl2Category["url"], $exResponse->getMeta("HTTP_LOCATION"));
        }
    }

    /**
     * Provides test cases for the most recent join with permission test.
     */
    public function providerMostRecentDataProvider()
    {
        // $role, $prefix, $expect
        return [
            ["member", "mem", ["title" => "", "userID" => null]],
            ["admin", "admin", ["title" => "privateDiscussion", "userID" => "apiUser"]],
        ];
    }

    /**
     * Test that users are sent to the category home when trying to reach the root category.
     */
    public function testRootCategoryAccessFail()
    {
        $result = $this->api()
            ->get("/categories/-1")
            ->getBody();
        try {
            $this->bessy()->get($result["url"] . "/" . $result["urlcode"])->Data;
        } catch (ResponseException $ex) {
            $response = $ex->getResponse();
            $this->assertSame(302, $response->getStatus());
            $this->assertStringEndsWith("/categories", $response->getMeta("HTTP_LOCATION"));
        }
    }

    /**
     * Test most recent discussion display captures the last commenter name and the last comment date .
     */
    public function testMostRecentUserAndDate()
    {
        $member1 = [
            "userID" => $this->createUserFixture(VanillaTestCase::ROLE_MEMBER, "_mem1"),
            "Name" => "Member_mem1",
        ];

        $member2 = [
            "userID" => $this->createUserFixture(VanillaTestCase::ROLE_MEMBER, "_mem2"),
            "Name" => "Member_mem2",
        ];
        $this->resetTable("Category");
        $this->resetTable("Discussion");
        $this->resetTable("Comment");
        $data = [];
        //create categories
        $parentCategory = $this->createCategory([
            "name" => "recent test",
            "Description" => "Prent category for the recent test",
        ]);
        $childCategory1 = $this->createCategory([
            "parentCategoryID" => $parentCategory["categoryID"],
            "name" => "SectionA",
        ]);
        $childCategory2 = $this->createCategory([
            "parentCategoryID" => $parentCategory["categoryID"],
            "name" => "SectionB",
        ]);
        $data[$childCategory1["categoryID"]]["CategoryID"] = $childCategory1["categoryID"];
        $data[$childCategory2["categoryID"]]["CategoryID"] = $childCategory2["categoryID"];

        //create Discussions
        $discussion1 = $this->createDiscussion([
            "categoryID" => $childCategory1["categoryID"],
            "name" => "openDiscussionA",
        ]);
        $discussion2 = $this->createDiscussion([
            "categoryID" => $childCategory2["categoryID"],
            "name" => "openDiscussionB",
        ]);
        $data[$childCategory1["categoryID"]]["DiscussionID"] = $discussion1["discussionID"];
        $data[$childCategory2["categoryID"]]["DiscussionID"] = $discussion2["discussionID"];
        //create comments
        $this->getSession()->start($member1["userID"]);
        $data[$childCategory1["categoryID"]]["Comment"] = $this->createComment([
            "discussionID" => $discussion1["discussionID"],
            "body" => "This is the comment for discussion 1",
        ]);
        $data[$childCategory1["categoryID"]]["User"] = $member1;

        $this->getSession()->start($member2["userID"]);
        $data[$childCategory2["categoryID"]]["Comment"] = $this->createComment([
            "discussionID" => $discussion2["discussionID"],
            "body" => "This is the comment for discussion 2",
        ]);
        $data[$childCategory2["categoryID"]]["User"] = $member2;

        $this->runWithConfig(["Vanilla.Comments.PerPage" => 1], function () use ($data) {
            $responses = $this->bessy()
                ->get("/categories")
                ->data("CategoryTree");
            $categories = $responses[0]["Children"];

            foreach ($categories as $category) {
                $commentUser = $data[$category["CategoryID"]]["User"];
                $comment = $data[$category["CategoryID"]]["Comment"];
                $this->assertEquals($commentUser["userID"], $category["LastUserID"]);
                $this->assertEquals($commentUser["Name"], $category["LastName"]);
                $this->assertEquals(
                    date("Y-m-d H:i:s", strtotime($comment["dateInserted"])),
                    $category["LastDateInserted"]
                );
            }
        });
    }
}
