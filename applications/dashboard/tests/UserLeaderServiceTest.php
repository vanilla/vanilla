<?php
/**
 * @author David Barbier <dbarbier@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Dashboard;

use CategoryModel;
use Garden\Container\Container;
use Gdn;
use Vanilla\Dashboard\UserLeaderService;
use Vanilla\Web\Controller;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\SiteTestCase;

/**
 * Tests for UserLeaderService.
 */
class UserLeaderServiceTest extends SiteTestCase
{
    use CommunityApiTestTrait;

    /** @var UserLeaderService */
    private $userLeaderService;

    /** @var CategoryModel */
    private $categoryModel;

    /**
     * {@inheritDoc}
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->userLeaderService = Gdn::getContainer()->get(UserLeaderService::class);
        $this->categoryModel = Gdn::getContainer()->get(CategoryModel::class);
    }

    /**
     * Test the getPointsCategory() function.
     */
    public function testGetPointsCategory()
    {
        // Create a root category.
        $categoryA = $this->createCategory();
        // Create a sub-category to $categoryA
        $categoryB = $this->createCategory(["ParentCategoryID" => $categoryA["categoryID"]]);
        // Create a sub-category to $categoryB
        $categoryC = $this->createCategory(["ParentCategoryID" => $categoryB["categoryID"]]);

        // Verify that none of our newly created categories has a custom points Category.
        $this->assertEquals(null, $this->userLeaderService->getPointsCategory($categoryA["categoryID"]));
        $this->assertEquals(null, $this->userLeaderService->getPointsCategory($categoryB["categoryID"]));
        $this->assertEquals(null, $this->userLeaderService->getPointsCategory($categoryC["categoryID"]));

        // Track categoryA leaderboard points separately.
        $this->categoryModel->save(["CategoryID" => $categoryA["categoryID"], "CustomPoints" => true]);
        // Track categoryC leaderboard points separately.
        $this->categoryModel->save(["CategoryID" => $categoryC["categoryID"], "CustomPoints" => true]);

        // Verify that categoryA has a custom points Category.
        $this->assertEquals(
            $categoryA["categoryID"],
            $this->userLeaderService->getPointsCategory($categoryA["categoryID"])["CategoryID"]
        );
        // Verify that categoryB doesn't have a custom points Category.
        $this->assertEquals(null, $this->userLeaderService->getPointsCategory($categoryB["categoryID"]));
        // Verify that categoryC has a custom points Category.
        $this->assertEquals(
            $categoryC["categoryID"],
            $this->userLeaderService->getPointsCategory($categoryC["categoryID"])["CategoryID"]
        );
    }
}
