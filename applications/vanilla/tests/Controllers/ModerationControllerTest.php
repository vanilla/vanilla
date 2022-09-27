<?php
/**
 * @author Isis Graziatto <isis.g@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Forum\Controllers;

use VanillaTests\Forum\Utils\TestModerationControllerTrait;
use VanillaTests\Models\TestCategoryModelTrait;
use VanillaTests\SiteTestCase;
use VanillaTests\Models\TestDiscussionModelTrait;

/**
 * Class ModerationControllerTest
 */
class ModerationControllerTest extends SiteTestCase
{
    use TestDiscussionModelTrait;
    use TestCategoryModelTrait;
    use TestModerationControllerTrait;

    /** @var array */
    private $discussions;

    /** @var array */
    private $category;

    /**
     * {@inheritDoc}
     */
    public static function getAddons(): array
    {
        return ["vanilla"];
    }

    /**
     * {@inheritDoc}
     */
    public function setUp(): void
    {
        parent::setUp();
        /** @var \Gdn_Configuration $config */
        $config = $this->container()->get(\Gdn_Configuration::class);
        $config->saveToConfig("Vanilla.Categories.Use", true);
        $this->discussions = $this->insertDiscussions(6);
        $this->category = $this->insertCategories(1)[0];
    }

    /**
     * Test ModerationController->confirmDiscussionMoves()
     */
    public function testConfirmDiscussionMoves(): void
    {
        $discussion = $this->discussions[0];
        $r = $this->moveDiscussion($discussion["DiscussionID"], $this->category);
        $this->assertTrue(in_array($discussion["DiscussionID"], array_column($r, "DiscussionID")));
    }

    /**
     * Test ModerationController->confirmDiscussionMoves()
     */
    public function testConfirmDiscussionMovesWithRedirectLink(): void
    {
        $discussion = $this->discussions[0];
        $category = $this->categoryModel->getID($discussion["CategoryID"], DATASET_TYPE_ARRAY);
        $r = $this->moveDiscussion($discussion["DiscussionID"], $this->category, ["RedirectLink" => "1"]);
        $this->assertTrue(in_array($discussion["DiscussionID"], array_column($r, "DiscussionID")));

        //assert that the first category still has all its discussions
        $updatedCategory = $this->categoryModel->getID($discussion["CategoryID"], DATASET_TYPE_ARRAY);
        $this->assertEquals($category["CountDiscussions"], $updatedCategory["CountDiscussions"]);
    }

    /**
     * Test ModerationController->confirmDiscussionMoves() with multiple discussions
     */
    public function testConfirmDiscussionMovesWithDiscussionIDs(): void
    {
        $r = $this->moveDiscussion(null, $this->category, [
            "discussionIDs" => array_column($this->discussions, "DiscussionID"),
        ]);
        $this->assertCount(count($this->discussions), $r);
    }

    /**
     * Test moving a discussion out of an archived category.
     */
    public function testMoveFromArchivedCategory(): void
    {
        $discussion = $this->discussions[0];
        $categoryToArchive = $this->categoryModel->getID($discussion["CategoryID"], DATASET_TYPE_ARRAY);
        $this->categoryModel->setField($categoryToArchive["CategoryID"], ["Archived" => 1]);
        $archivedCategory = $this->categoryModel->getID($discussion["CategoryID"], DATASET_TYPE_ARRAY);
        $this->assertSame($archivedCategory["Archived"], 1);
        $discussions = $this->moveDiscussion($discussion["DiscussionID"], $this->category);
        $this->assertTrue(in_array($discussion["DiscussionID"], array_column($discussions, "DiscussionID")));
    }

    /**
     * Test ModerationController::DiscussionDeletes()
     */
    public function testConfirmDiscussionDeletes(): void
    {
        $discussion1 = $this->discussions[3];
        $discussion2 = $this->discussions[4];
        $discussionIDs = [$discussion1["DiscussionID"], $discussion2["DiscussionID"]];
        $data = [
            "discussionIDs" => $discussionIDs,
        ];
        $response = $this->bessy()->post("/moderation/confirmdiscussiondeletes", $data);
        $result = $this->discussionModel->getIn($discussionIDs)->resultArray();
        $this->assertEquals(0, count($result));
        $this->assertEquals(2, $response->Data["CountCheckedDiscussions"]);
        $this->assertEquals(2, $response->Data["CountAllowed"]);
        $this->assertEquals(0, $response->Data["CountNotAllowed"]);

        // Test with some random ids.
        $discussionIDs = [rand(3000, 4000), rand(3000, 4000)];
        $data = [
            "discussionIDs" => $discussionIDs,
        ];
        $response = $this->bessy()->post("/moderation/confirmdiscussiondeletes", $data);
        $this->assertEquals(2, $response->Data["CountCheckedDiscussions"]);
        $this->assertEquals(0, $response->Data["CountAllowed"]);
        $this->assertEquals(2, $response->Data["CountNotAllowed"]);
    }
}
