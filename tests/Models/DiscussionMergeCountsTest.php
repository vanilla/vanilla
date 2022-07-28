<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Models;

use Vanilla\Models\Model;
use Vanilla\Utility\ModelUtils;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Verify discussion count routines.
 */
class DiscussionMergeCountsTest extends AbstractCountsTest
{
    use UsersAndRolesApiTestTrait;

    /**
     * We merge 2 discussions from different categories & verify that the top-most parent categories' counts are ok.
     *
     * @throws \Gdn_UserException Throws a User Exception.
     */
    public function testMergeDiscussionsDifferentCategoriesAncestorsCategoriesCounts(): void
    {
        $sourceDiscussionID = $destDiscussionID = null;
        $parentSourceCategoryID = $parentDestCategoryID = null;

        // Pick a source discussion that's in a second-level category.
        foreach ($this->discussions as $discussion) {
            if ($this->categories[$discussion["CategoryID"]]["Depth"] == 2) {
                $sourceDiscussionID = $discussion["DiscussionID"];
                $parentSourceCategoryID = $this->getRootCategoryID($discussion["CategoryID"]);
                break;
            }
        }

        // Pick a destination discussion in a second-level category & has a different root category than the source.
        foreach ($this->discussions as $discussion) {
            if ($this->categories[$discussion["CategoryID"]]["Depth"] == 2) {
                $parentDestCategoryID = $this->getRootCategoryID($discussion["CategoryID"]);
                if ($parentDestCategoryID != $parentSourceCategoryID) {
                    $destDiscussionID = $discussion["DiscussionID"];
                    break;
                }
            }
        }

        $sourceDiscussion = $this->discussions[$sourceDiscussionID];
        $destDiscussion = $this->discussions[$destDiscussionID];

        $parentSourceCategory = $this->categories[$parentSourceCategoryID];
        $parentDestCategory = $this->categories[$parentDestCategoryID];

        ModelUtils::consumeGenerator(
            $this->mergeModel->mergeDiscussionsIterator(
                [$sourceDiscussion["DiscussionID"]],
                $destDiscussion["DiscussionID"],
                false
            )
        );

        $this->reloadCategoriesDiscussionsComments();

        // Test 'CountDiscussions'
        // The top-most source category used to have no discussion directly under it. Post-merge there is still none.
        $this->assertSame($parentSourceCategory["CountDiscussions"], 0);
        $this->assertSame($this->categories[$parentSourceCategoryID]["CountDiscussions"], 0);
        // The top-most destination category used to have no discussion directly under it. Status quo post-merge.
        $this->assertSame($parentDestCategory["CountDiscussions"], 0);
        $this->assertSame($this->categories[$parentDestCategoryID]["CountDiscussions"], 0);

        // Test 'CountAllDiscussions'
        // The top-most source category used should have "lost" 1 discussion from the merge.
        $this->assertSame(
            $this->categories[$parentSourceCategoryID]["CountAllDiscussions"],
            $parentSourceCategory["CountAllDiscussions"] - 1
        );
        // The top-most destination category should have the same amount of discussion pre & post merge4 as the
        // merged discussion has been converted to a comment.
        $this->assertSame(
            $parentDestCategory["CountAllDiscussions"],
            $this->categories[$parentDestCategoryID]["CountAllDiscussions"]
        );

        // Test 'CountComments'
        // The top-most source category should have the same amount of direct comments prior & after merge.
        $this->assertSame(
            $parentSourceCategory["CountComments"],
            $this->categories[$parentSourceCategoryID]["CountComments"]
        );
        // The top-most destination category should have the same amount of direct comments prior & after merge.
        $this->assertSame(
            $parentDestCategory["CountComments"],
            $this->categories[$parentDestCategoryID]["CountComments"]
        );

        // Test 'CountAllComments.
        $this->assertSame(
            $this->categories[$parentSourceCategoryID]["CountAllComments"],
            $parentSourceCategory["CountAllComments"] - $sourceDiscussion["CountComments"],
            'The top-most source category comment counts should be the amount of it\'s original value minus the amount of comments that\'s been moved.'
        );

        // The top-most destination category used to have 20 total comments(split between 4 discussions).
        // It gained 5 comments from the merge, plus 1 for the discussion that's been converted to a comment
        // for a total of 26.
        $this->assertSame(
            $this->categories[$parentDestCategoryID]["CountAllComments"],
            $parentDestCategory["CountAllComments"] + $sourceDiscussion["CountComments"] + 1
        );

        // Make sure the rest of the counts flowed through properly.
        $this->assertAllCounts();
    }

    /**
     * We merge 2 discussions from different categories & verify that the comments are transferred as well as their
     * counts.
     *
     * @throws \Gdn_UserException Throws a User Exception.
     */
    public function testMergeDiscussionsDifferentCategoriesDiscussionsCounts(): void
    {
        $destDiscussion = reset($this->discussions);

        // We pick a source discussion that's from a different category than it's destination.
        foreach ($this->discussions as $discussionID => $discussion) {
            if ($discussion["CategoryID"] != $destDiscussion["CategoryID"]) {
                $sourceDiscussion = $discussion;
                break;
            }
        }
        $this->assertNotNull($sourceDiscussion);

        ModelUtils::consumeGenerator(
            $this->mergeModel->mergeDiscussionsIterator(
                [$sourceDiscussion["DiscussionID"]],
                $destDiscussion["DiscussionID"],
                false
            )
        );

        $this->reloadCategoriesDiscussionsComments();

        // Make sure the source discussion was deleted.
        $this->assertFalse($this->discussions[$sourceDiscussion["DiscussionID"]] ?? false);

        // Make sure the destination discussion got the comment count from the merge
        // as well as the discussion that's been converted to a comment.
        $destDiscussionReloaded = $this->discussions[$destDiscussion["DiscussionID"]] ?? false;
        $this->assertSame(
            $sourceDiscussion["CountComments"] + $destDiscussion["CountComments"] + 1,
            $destDiscussionReloaded["CountComments"]
        );

        // Make sure the rest of the counts flowed through properly.
        $this->assertAllCounts();
    }

    /**
     * We merge 2 discussions from different categories & verify that the categories' counts are appropriate.
     *
     * @throws \Gdn_UserException Throws a User Exception.
     */
    public function testMergeDiscussionsDifferentCategoriesFirstLevelCategoriesCounts(): void
    {
        $destDiscussion = reset($this->discussions);

        // We pick a source discussion that's from a different category than it's destination.
        foreach ($this->discussions as $discussionID => $discussion) {
            if ($discussion["CategoryID"] != $destDiscussion["CategoryID"]) {
                $sourceDiscussion = $discussion;
                break;
            }
        }
        $this->assertNotNull($sourceDiscussion);

        $sourceCategoryID = $sourceDiscussion["CategoryID"] ?? false;
        $sourceCategory = $this->categories[$sourceCategoryID];

        $destCategoryID = $destDiscussion["CategoryID"] ?? false;
        $destCategory = $this->categories[$destCategoryID];

        ModelUtils::consumeGenerator(
            $this->mergeModel->mergeDiscussionsIterator(
                [$sourceDiscussion["DiscussionID"]],
                $destDiscussion["DiscussionID"],
                false
            )
        );

        $this->reloadCategoriesDiscussionsComments();

        // Test 'CountDiscussions'
        // Post merge, the source category has 1 less discussion than it previously had.
        $this->assertSame(
            $sourceCategory["CountDiscussions"],
            $this->categories[$sourceCategoryID]["CountDiscussions"] + 1
        );
        // The destination category has the same amount of discussion that it had before.
        $this->assertSame($destCategory["CountDiscussions"], $this->categories[$destCategoryID]["CountDiscussions"]);

        // Test 'CountAllDiscussions'
        // The source category now has 1 less discussion than it had before.
        $this->assertSame(
            $sourceCategory["CountAllDiscussions"],
            $this->categories[$sourceCategoryID]["CountAllDiscussions"] + 1
        );
        // The destination category has the same amount of discussion that it had before.
        $this->assertSame(
            $destCategory["CountAllDiscussions"],
            $this->categories[$destCategoryID]["CountAllDiscussions"]
        );

        // Test 'CountComments'
        // The source category has (the amount of migrated comments) comments fewer than it had before.
        $this->assertSame(
            $sourceCategory["CountComments"] - $sourceDiscussion["CountComments"],
            $this->categories[$sourceCategoryID]["CountComments"]
        );
        // The destination category now has the previous amount of comments it had + the number of
        // comments from the source discussion + 1(as the source discussion has been converted to a comment.
        $this->assertSame(
            $this->categories[$destCategoryID]["CountComments"],
            $destCategory["CountComments"] + $sourceDiscussion["CountComments"] + 1
        );

        // Test 'CountAllComments'
        // The source category now has fewer(the merged discussion's comments amount) comments than it had prior merge.
        $this->assertSame(
            $sourceCategory["CountAllComments"],
            $this->categories[$sourceCategoryID]["CountAllComments"] + $sourceDiscussion["CountComments"]
        );
        // The destination category now has the previous amount of comments it had + the number of
        // comments from the source discussion + 1(as the source discussion has been converted to a comment.
        $this->assertSame(
            $this->categories[$destCategoryID]["CountAllComments"],
            $destCategory["CountAllComments"] + $sourceDiscussion["CountComments"] + 1
        );

        // Make sure the rest of the counts flowed through properly.
        $this->assertAllCounts();
    }

    /**
     * Returns the root categoryID for a provided categoryID.
     *
     * @param int $categoryID
     * @return int
     */
    private function getRootCategoryID(int $categoryID): int
    {
        $newCategoryID = $this->categories[$categoryID]["ParentCategoryID"];

        return $newCategoryID == -1 ? $categoryID : $this->getRootCategoryID($newCategoryID);
    }

    /**
     * Test recalculation of bookmarks counts.
     */
    public function testBookmarkCounts()
    {
        $this->enableCaching();
        $user1 = $this->createUser();
        $user2 = $this->createUser();
        $user3 = $this->createUser();

        /** @var Model $model */
        $model = self::container()->getArgs(Model::class, ["UserDiscussion"]);
        $model->insert(["UserID" => $user1["userID"], "DiscussionID" => 4, "Bookmarked" => true]);
        $model->insert(["UserID" => $user1["userID"], "DiscussionID" => 3, "Bookmarked" => true]);
        $model->insert(["UserID" => $user2["userID"], "DiscussionID" => 1, "Bookmarked" => true]);
        $model->insert(["UserID" => $user3["userID"], "DiscussionID" => 3, "Bookmarked" => true]);

        $this->discussionModel->recalculateBookmarkCounts(3);
        $this->discussionModel->recalculateBookmarkCounts(1);

        $this->assertBoomarkCount(2, $user1["userID"]);
        $this->assertBoomarkCount(1, $user2["userID"]);
        $this->assertBoomarkCount(1, $user3["userID"]);
    }

    /**
     * Assert that a user has a certain bookmark count.
     *
     * @param int $expected The expected count.
     * @param int $userID The userID to check.
     */
    private function assertBoomarkCount(int $expected, int $userID)
    {
        $user = $this->userModel->getID($userID, DATASET_TYPE_ARRAY);
        $this->assertEquals($expected, $user["CountBookmarks"]);
    }
}
