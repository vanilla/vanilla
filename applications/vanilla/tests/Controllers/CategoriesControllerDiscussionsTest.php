<?php
/**
 * @author Richard Flynn <richard.flynn@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Forum\Controllers;

use VanillaTests\APIv2\AbstractAPIv2Test;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;

/**
 * Test the categoriescontroller's discussions() method.
 */
class CategoriesControllerDiscussionsTest extends AbstractAPIv2Test {
    use CommunityApiTestTrait;

    /** @var Gdn_Configuration */
    private $configuration;

    /**
     * @inheritDoc
     */
    public function setUp() : void {
        parent::setUp();

        $this->configuration = static::container()->get('Config');
        $this->configuration->set('Vanilla.Categories.Layout', 'mixed');
        $this->configuration->set('Vanilla.Categories.Use', true);
    }

    /**
     * Test that announced discussions are delivered and appear before all other discussions.
     */
    public function testAnnouncementsPinnedComeFirst() : void {
        $category = $this->createCategory();
        $disc1 = $this->createDiscussion(['pinned' => true, 'categoryID' => $category['categoryID']]);
        $disc2 = $this->createDiscussion(['pinned' => true, 'categoryID' => $category['categoryID']]);
        $disc3 = $this->createDiscussion(['pinned' => false, 'categoryID' => $category['categoryID']]);
        $disc4 = $this->createDiscussion(['pinned' => false, 'categoryID' => $category['categoryID']]);

        $discussions = $this->bessy()->get("/categories")->data("Discussions");
        $this->assertSame($discussions[0]->DiscussionID, $disc2["discussionID"]);
        $this->assertSame($discussions[1]->DiscussionID, $disc1["discussionID"]);
        $this->assertSame($discussions[2]->DiscussionID, $disc4["discussionID"]);
        $this->assertSame($discussions[3]->DiscussionID, $disc3["discussionID"]);
    }

    /**
     * Test that only announcement are delivered.
     */
    public function testOnlyAnnouncementsDisplay() : void {
        $category = $this->createCategory();
        $this->createDiscussion(['pinned' => true, 'categoryID' => $category['categoryID']]);
        $this->createDiscussion(['pinned' => true, 'categoryID' => $category['categoryID']]);
        $this->createDiscussion(['pinned' => true, 'categoryID' => $category['categoryID']]);
        $this->createDiscussion(['pinned' => true, 'categoryID' => $category['categoryID']]);
        $this->createDiscussion(['pinned' => true, 'categoryID' => $category['categoryID']]);
        $disc6 = $this->createDiscussion(['pinned' => false, 'categoryID' => $category['categoryID']]);

        $discussions = $this->bessy()->get("/categories")->data("Discussions");

        $discussionIDs = [];
        foreach ($discussions as $discussion) {
            if ($discussion->CategoryID === $category["categoryID"]) {
                $discussionIDs[] = $discussion->DiscussionID;
            }
        }

        $this->assertNotContains($disc6["discussionID"], $discussionIDs);
    }
}
