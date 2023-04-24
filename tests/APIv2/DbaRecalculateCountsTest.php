<?php
/**
 * @author Sooraj Francis <sfrancis@higherlogic.com>
 * @copyright 2009-2022 Higher Logic LLC.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

use Vanilla\CurrentTimeStamp;
use VanillaTests\ExpectExceptionTrait;
use VanillaTests\Models\TestCategoryModelTrait;
use VanillaTests\Models\TestCommentModelTrait;
use VanillaTests\Models\TestDiscussionModelTrait;
use VanillaTests\SchedulerTestTrait;
use VanillaTests\SiteTestCase;
use VanillaTests\UsersAndRolesApiTestTrait;
use VanillaTests\VanillaTestCase;

/**
 * Test Recalculate counts for discussions.
 */
class DbaRecalculateCountsTest extends SiteTestCase
{
    use SchedulerTestTrait;
    use UsersAndRolesApiTestTrait;
    use ExpectExceptionTrait;
    use TestCategoryModelTrait;
    use TestDiscussionModelTrait;
    use TestCommentModelTrait;

    /** @var \ConversationModel */
    protected $conversationModel;

    /**
     * @inheritDoc
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->resetTable("Category");
        $this->resetTable("Comment");
        $this->resetTable("Discussion");
        $this->resetTable("Conversation");
        $this->conversationModel = \Gdn::getContainer()->get(\ConversationModel::class);
        $this->sql = $this->discussionModel->SQL;
        $this->categories = $this->discussions = $this->comments = $this->conversations = $this->users = [];
        $this->batches = 1;
        CurrentTimeStamp::mockTime("2022-01-01");
        \Gdn::config()->set("Dba.Limit", 2);
        $this->setDicussionCountTestData();
    }

    /**
     * Test a user with less permission trying to run recalculate counts gets  permission error.
     */
    public function testInvalidPermissions()
    {
        $memberID = $this->createUserFixture(VanillaTestCase::ROLE_MEMBER);
        $this->runWithUser(function () {
            $this->runWithExpectedExceptionCode(403, function () {
                $body = [
                    "aggregates" => ["discussion.*"],
                ];
                $this->api()->put("/dba/recalculate-aggregates", $body);
            });
        }, $memberID);
    }

    /**
     * Test a user trying to run recalculate counts with missing permissions.
     */
    public function testgetInvalidValidation()
    {
        $modId = $this->createUserFixture(VanillaTestCase::ROLE_MOD);
        $this->runWithUser(function () {
            $this->runWithExpectedExceptionCode(422, function () {
                $body = [
                    "aggregates" => ["random"],
                ];
                $this->api()->put("/dba/recalculate-aggregates", $body);
            });
        }, $modId);
    }

    /**
     *Test the Discussion Counts
     */

    public function testDiscussionCounts()
    {
        $this->getLongRunner()->setMaxIterations(1000000);
        $set = [
            "FirstCommentID" => 0,
            "LastCommentID" => 0,
            "CountComments" => 0,
            "DateLastComment" => "1970-01-01 00:00:00",
            "LastCommentUserID" => 0,
        ];
        $this->resetTableCounts("Discussion", "DiscussionID", array_keys($this->discussions), $set);
        $body = [
            "aggregates" => ["discussion.*"],
        ];
        $response = $this->api()->put("/dba/recalculate-aggregates", $body);
        // Request should be successful.
        $this->assertEquals(200, $response->getStatusCode());
        $body = $response->getBody();
        $this->assertEquals($this->batches, count($body["progress"]["successIDs"]));
        $this->assertEquals($this->batches, $body["progress"]["countTotalIDs"]);
        $this->assertNull($body["callbackPayload"]);
        $this->assertEmpty($body["progress"]["failedIDs"]);
        $randomDiscussionIds = array_rand($this->discussions, 2);
        $updatedDiscussions = $this->discussionModel
            ->getWhere(["DiscussionID" => $randomDiscussionIds, "Announce" => false])
            ->resultArray();
        $fieldsToCompare = array_keys($set);
        foreach ($updatedDiscussions as $updatedDiscussion) {
            $discussionId = $updatedDiscussion["DiscussionID"];
            foreach ($fieldsToCompare as $field) {
                $this->assertEquals(
                    $this->discussions[$discussionId][$field],
                    $updatedDiscussion[$field],
                    "Incorrect $field for Discussion $discussionId"
                );
            }
        }
    }

    /** Set discussion data for testing */
    private function setDicussionCountTestData()
    {
        // Insert some category containers.
        $parentCategories = $this->insertCategories(2, [
            "Name" => "Parent Count %s",
            "DisplayAs" => \CategoryModel::DISPLAY_NESTED,
        ]);
        $this->categories += array_column($parentCategories, null, "CategoryID");

        foreach ($parentCategories as $category) {
            $childCategories = $this->insertCategories(2, [
                "Name" => "Test Count %s",
                "DisplayAs" => \CategoryModel::DISPLAY_DISCUSSIONS,
                "ParentCategoryID" => $category["CategoryID"],
            ]);

            $this->categories += array_column($childCategories, null, "CategoryID");

            foreach ($childCategories as $childCategory) {
                // Insert some test discussions.
                $discussions = $this->insertDiscussions(2, ["CategoryID" => $childCategory["CategoryID"]]);
                $this->discussions += array_column($discussions, null, "DiscussionID");

                // Insert some comments for each discussion.
                foreach ($discussions as $discussion) {
                    $comments = $this->insertComments(5, ["DiscussionID" => $discussion["DiscussionID"]]);
                    $this->comments += array_column($comments, null, "CommentID");
                }
            }
        }
        $this->batches = (int) ceil(count($this->discussions) / \Gdn::config("Dba.Limit"));
        $this->reloadDiscussions();
    }

    /**
     * Reloads Discussions  to the class member.
     */
    private function reloadDiscussions(): void
    {
        // Reload discussions
        $discussionsRows = $this->discussionModel
            ->getWhere(["DiscussionID" => array_keys($this->discussions), "Announce" => false])
            ->resultArray();
        $this->discussions = [];
        foreach ($discussionsRows as $discussionsRow) {
            $this->discussions[$discussionsRow["DiscussionID"]] = $discussionsRow;
        }
    }

    /**
     * Reset Counts for Table.
     *
     * @var string $table
     * @var String $key
     * @var array $Ids
     * @var array $columns
     */

    private function resetTableCounts(string $table, string $key, array $Ids, array $columns): void
    {
        $where = [$key => $Ids];
        $query = $this->sql->update($table, $columns, $where)->getUpdateSql();
        $this->sql->Database->query($query, $columns);
        $this->sql->reset();
    }

    /**
     * Test if we are receiving a successful counts and failed counts and payload
     * on in case of time out
     */
    public function testTimeout()
    {
        $body = [
            "tables" => ["discussion"],
        ];
        $this->getLongRunner()->setMaxIterations(1);
        $this->runWithExpectedExceptionCode(408, function () {
            $body = [
                "aggregates" => ["discussion.*"],
            ];
            $response = $this->api()->put("/dba/recalculate-aggregates", $body);
            $this->assertEquals(408, $response->getStatusCode());
            $body = $response->getBody()["counts"];
            $this->assertEquals($this->batches, $body["progress"]["countTotalIDs"]);
            $this->assertCount(1, $body["progress"]["successIDs"]);
            $this->assertNotNull($body["callbackPayload"]);
        });
    }

    /**
     *Test the Conversations Counts
     */
    public function testConversationCounts()
    {
        $this->setConverstionCountTestData();

        $body = [
            "aggregates" => ["conversation.*"],
        ];
        $response = $this->api()->put("/dba/recalculate-aggregates", $body);
        $batches = (int) ceil(count($this->conversations) / \Gdn::config("Dba.Limit"));
        // Request should be successful.
        $this->assertEquals(200, $response->getStatusCode());
        $body = $response->getBody();
        $this->assertEquals($batches, count($body["progress"]["successIDs"]));
        $this->assertEquals($batches, $body["progress"]["countTotalIDs"]);
        $this->assertNull($body["callbackPayload"]);
        $this->assertEmpty($body["progress"]["failedIDs"]);
        $updatedConversations = $this->conversationModel
            ->getWhere(["ConversationID" => array_keys($this->conversations)])
            ->resultArray();
        $fieldsToCompare = ["CountMessages", "CountParticipants", "FirstMessageID", "LastMessageID", "DateUpdated"];
        foreach ($updatedConversations as $updatedConversation) {
            $conversationId = $updatedConversation["ConversationID"];
            foreach ($fieldsToCompare as $field) {
                $this->assertEquals($this->conversations[$conversationId][$field], $updatedConversation[$field]);
            }
            $this->assertEquals(
                $this->conversations[$conversationId]["InsertUserID"],
                $updatedConversation["UpdateUserID"]
            );
        }
    }

    /** Set discussion data for testing */
    private function setConverstionCountTestData()
    {
        if (!empty($this->conversations)) {
            return;
        }
        $users = $this->getTestUsers(3);
        //create multiple conversations for testing
        $messageUser = array_pop($users);
        $conversation = [
            "Format" => "Text",
            "Body" => "Creating conversation between " . join(",", $users),
            "InsertUserID" => $messageUser,
            "RecipientUserID" => array_slice($users, 1),
        ];
        $conversationID[] = $this->conversationModel->save($conversation);

        foreach ($users as $user) {
            $conversation["Body"] = "Conversation between $messageUser and $user";
            $conversation["RecipientUserID"] = [$user];
            $conversationID[] = $this->conversationModel->save($conversation);
        }
        $conversations = $this->conversationModel->getWhere(["ConversationID" => $conversationID])->resultArray();
        foreach ($conversations as $conversation) {
            $this->conversations[$conversation["ConversationID"]] = $conversation;
        }
        $columns = [
            "CountMessages" => 0,
            "CountParticipants" => 0,
            "FirstMessageID" => null,
            "LastMessageID" => null,
            "DateUpdated" => "1970-01-01 00:00:00",
        ];
        $this->resetTableCounts("Conversation", "ConversationID", $conversationID, $columns);
    }

    /**
     * Get an array of users for testing
     *
     * @param int $counts
     * @return array
     */
    protected function getTestUsers(int $counts = 0): array
    {
        $currentCounts = count($this->users);
        if ($currentCounts > $counts) {
            return array_slice($this->users, 0, $counts);
        } else {
            $counts = $counts - $currentCounts;
            for ($i = 0; $i < $counts; $i++) {
                $this->users[] = $this->createUser()["userID"];
            }
        }

        return $this->users;
    }

    /**
     *   Test can process multiple tables
     */
    public function testMultipleTables()
    {
        $this->setConverstionCountTestData();

        $body = [
            "aggregates" => ["discussion.*", "conversation.*"],
        ];
        $response = $this->api()->put("/dba/recalculate-aggregates", $body);
        $body = $response->getBody();
        $successIds = $body["progress"]["successIDs"];
        $this->assertEquals(6, $body["progress"]["countTotalIDs"]);

        $this->assertEmpty($body["progress"]["failedIDs"]);
        $this->assertEquals(
            [
                "Discussion_CountComments_0",
                "Discussion_CountComments_1",
                "Discussion_CountComments_2",
                "Discussion_CountComments_3",
                "Conversation_CountMessages_0",
                "Conversation_CountMessages_1",
            ],
            $successIds
        );
    }

    /**
     *Test the Category Counts
     */
    public function testCategoryCounts()
    {
        $this->reloadCategories();

        $columns = [
            "CountCategories" => 0,
            "CountDiscussions" => 0,
            "CountAllDiscussions" => 0,
            "CountComments" => 0,
            "CountAllComments" => 0,
            "LastCommentID" => null,
            "LastDiscussionID" => null,
            "LastDateInserted" => null,
        ];
        $this->resetTableCounts("Category", "CategoryID", array_keys($this->categories), $columns);
        $body = [
            "aggregates" => ["category.*"],
        ];
        $response = $this->api()->put("/dba/recalculate-aggregates", $body);
        // Request should be successful.
        $this->assertEquals(200, $response->getStatusCode());
        $body = $response->getBody();
        $updatedCategories = $this->categoryModel
            ->getWhere(["CategoryID" => array_column($this->categories, "CategoryID")])
            ->resultArray();
        $fieldsToCompare = array_keys($columns);
        foreach ($updatedCategories as $updatedCategory) {
            $categoryId = $updatedCategory["CategoryID"];
            foreach ($fieldsToCompare as $field) {
                $this->assertEquals(
                    $this->categories[$categoryId][$field],
                    $updatedCategory[$field],
                    "Incorrect $field for Category $categoryId"
                );
            }
        }
    }

    /**
     * Reloads Categories to the class member.
     */
    private function reloadCategories()
    {
        // Reload discussions
        $categoryRows = $this->categoryModel->getWhere(["CategoryID" => array_keys($this->categories)])->resultArray();
        $this->categories = [];
        foreach ($categoryRows as $categoryRow) {
            $this->categories[$categoryRow["CategoryID"]] = $categoryRow;
        }
    }
}
