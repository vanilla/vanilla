<?php
/**
 * @author Sooraj Francis <sfrancis@higherlogic.com>
 * @copyright 2009-2022 Higher Logic LLC.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

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
    use TestCategoryModelTrait, TestDiscussionModelTrait, TestCommentModelTrait;

    /** @var \ConversationModel */
    protected $conversationModel;

    /**
     * @inheritDoc
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->resetTable("Comment");
        $this->resetTable("Discussion");
        $this->resetTable("Conversation");
        $this->conversationModel = \Gdn::getContainer()->get(\ConversationModel::class);
        $this->sql = $this->discussionModel->SQL;
        $this->categories = $this->discussions = $this->comments = $this->conversations = $this->users = [];
        $this->dataCreated = false;
        $this->batches = 1;
        \Gdn::config()->set("Dba.Limit", 2);
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
                    "tables" => ["discussion"],
                ];
                $this->api()->patch("/dba/counts", $body);
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
                    "tables" => ["random"],
                ];
                $this->api()->patch("/dba/counts", $body);
            });
        }, $modId);
    }

    /**
     *Test the Discussion Counts
     */

    public function testDiscussionCounts()
    {
        $this->setDicussionCountTestData();
        $body = [
            "tables" => ["discussion"],
        ];
        $response = $this->api()->patch("/dba/counts", $body);
        // Request should be successful.
        $this->assertEquals(200, $response->getStatusCode());
        $body = $response->getBody();
        $this->assertEquals($this->batches, count($body["progress"]["successIDs"]));
        $this->assertEquals($this->batches, $body["progress"]["countTotalIDs"]);
        $this->assertNull($body["callbackPayload"]);
        $this->assertEmpty($body["progress"]["failedIDs"]);
        $randomDiscussionIds = array_rand(array_keys($this->discussions), 3);
        $updatedDiscussions = $this->discussionModel
            ->getWhere(["DiscussionID" => array_keys($randomDiscussionIds)])
            ->resultArray();
        $fieldsToCompare = ["FirstCommentID", "LastCommentID", "CountComments", "DateLastComment", "LastCommentUserID"];
        foreach ($updatedDiscussions as $updatedDiscussion) {
            $discussionId = $updatedDiscussion["DiscussionID"];
            foreach ($fieldsToCompare as $field) {
                $this->assertEquals($this->discussions[$discussionId][$field], $updatedDiscussion[$field]);
            }
        }
    }

    /** Set discussion data for testing */
    private function setDicussionCountTestData()
    {
        if (!$this->dataCreated) {
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
            $set = [
                "FirstCommentID" => 0,
                "LastCommentID" => 0,
                "CountComments" => 0,
                "DateLastComment" => "1970-01-01 00:00:00",
                "LastCommentUserID" => 0,
            ];
            $this->resetTableCounts("Discussion", "DiscussionID", array_keys($this->discussions), $set);
            $this->dataCreated = true;
        }
    }

    /**
     * Reloads Discussions  to the class member.
     */
    private function reloadDiscussions(): void
    {
        // Reload discussions
        $discussionsRows = $this->discussionModel
            ->getWhere(["DiscussionID" => array_keys($this->discussions)])
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
        $this->setDicussionCountTestData();
        $body = [
            "tables" => ["discussion"],
        ];
        $this->getLongRunner()->setMaxIterations(1);
        $this->runWithExpectedExceptionCode(408, function () {
            $body = [
                "tables" => ["discussion"],
            ];
            $response = $this->api()->patch("/dba/counts", $body);
            $this->assertEquals(408, $response->getStatusCode());
            $body = $response->getBody();
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
            "tables" => ["conversation"],
        ];
        $response = $this->api()->patch("/dba/counts", $body);
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
        $this->setDicussionCountTestData();
        $body = [
            "tables" => ["discussion", "conversation"],
        ];
        $response = $this->api()->patch("/dba/counts", $body);
        $body = $response->getBody();
        $discussionBatch = range(1, (int) ceil(count($this->discussions) / \Gdn::config("Dba.Limit")));
        $conversationBatch = range(1, (int) (int) ceil(count($this->conversations) / \Gdn::config("Dba.Limit")));
        $totalBatch = array_merge($discussionBatch, $conversationBatch);
        $this->assertEquals($totalBatch, $body["progress"]["successIDs"]);
    }
}
