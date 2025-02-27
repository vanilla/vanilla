<?php
/**
 * @author Sooraj Francis <sfrancis@higherlogic.com>
 * @copyright 2009-2022 Higher Logic LLC.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

use Vanilla\CurrentTimeStamp;
use VanillaTests\ExpectExceptionTrait;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
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
    use CommunityApiTestTrait;

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
        $this->conversations = $this->users = [];
        $this->enableFeature("customLayout.post");
        CurrentTimeStamp::mockTime("2022-01-01");
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
        $this->setDicussionCountTestData();
        $this->getLongRunner()->setMaxIterations(1000000);
        $set = [
            "FirstCommentID" => 0,
            "LastCommentID" => 0,
            "CountComments" => 0,
            "DateLastComment" => "1970-01-01 00:00:00",
            "LastCommentUserID" => 0,
        ];
        $this->container()
            ->get(\DiscussionModel::class)
            ->update($set, []);

        $body = [
            "aggregates" => ["discussion.*"],
        ];
        $response = $this->api()->put("/dba/recalculate-aggregates", $body);
        // Request should be successful.
        $this->assertEquals(200, $response->getStatusCode());
        $body = $response->getBody();
        // There are 8 discussions with a batch size of 2. This makes 4 batches.
        // There are 6 total aggregates being calculated so we have 24 total iterations.
        $this->assertEquals(24, count($body["progress"]["successIDs"]));
        $this->assertEquals(24, $body["progress"]["countTotalIDs"]);
        $this->assertNull($body["callbackPayload"]);
        $this->assertEmpty($body["progress"]["failedIDs"]);
        $allDiscussionIDs = \Gdn::sql()
            ->select("DiscussionID")
            ->get("Discussion")
            ->column("DiscussionID");
        foreach ($allDiscussionIDs as $discussionID) {
            $this->assertDiscussionCounts($discussionID);
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

        foreach ($parentCategories as $category) {
            $childCategories = $this->insertCategories(2, [
                "Name" => "Test Count %s",
                "DisplayAs" => \CategoryModel::DISPLAY_DISCUSSIONS,
                "ParentCategoryID" => $category["CategoryID"],
            ]);

            foreach ($childCategories as $childCategory) {
                // Insert some test discussions.
                $discussions = $this->insertDiscussions(2, ["CategoryID" => $childCategory["CategoryID"]]);

                // Insert some comments for each discussion.
                foreach ($discussions as $discussion) {
                    $comments = $this->insertComments(5, ["DiscussionID" => $discussion["DiscussionID"]]);
                }
            }
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
    public function testStopAndResume()
    {
        $this->createCategory();
        $this->createDiscussion();
        $this->createDiscussion();
        $this->getLongRunner()->setMaxIterations(1);
        $body = [
            "aggregates" => ["discussion.*"],
        ];
        $response = $this->api()->put("/dba/recalculate-aggregates", $body, [], ["throw" => false]);
        $this->assertEquals(408, $response->getStatusCode());
        $body = $response->getBody();
        $this->assertEquals(6, $body["progress"]["countTotalIDs"]);
        $this->assertCount(1, $body["progress"]["successIDs"]);
        $this->assertNotNull($body["callbackPayload"]);

        $this->getLongRunner()->setMaxIterations(null);
        $finalResponse = $this->resumeLongRunner($response);
        $this->assertEquals(200, $finalResponse->getStatusCode());
        $body = $finalResponse->getBody();
        $this->assertEquals(6, $body["progress"]["countTotalIDs"]);
        $this->assertCount(5, $body["progress"]["successIDs"]);
        $this->assertNull($body["callbackPayload"]);
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
        // Request should be successful.
        $this->assertEquals(200, $response->getStatusCode());
        $body = $response->getBody();
        // 4 conversations with a batch size of 2 * 6 aggregates = 12 total iterations.
        $this->assertEquals(12, count($body["progress"]["successIDs"]));
        $this->assertEquals(12, $body["progress"]["countTotalIDs"]);
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
     * Test can process multiple tables
     */
    public function testMultipleTables()
    {
        $this->setDicussionCountTestData();
        $this->setConverstionCountTestData();
        $body = [
            "aggregates" => ["discussion.*", "conversation.*"],
        ];
        $response = $this->api()->put("/dba/recalculate-aggregates", $body);
        $body = $response->getBody();
        $successIds = $body["progress"]["successIDs"];
        // 2 conversation iterations & 6 total aggregates = 12 conversations iterations
        // 4 discussion iterations &
        $this->assertEquals(36, $body["progress"]["countTotalIDs"]);

        $this->assertEmpty($body["progress"]["failedIDs"]);
        $this->assertEquals(
            [
                "Discussion_CountComments_0",
                "Discussion_CountComments_1",
                "Discussion_CountComments_2",
                "Discussion_CountComments_3",
                "Discussion_FirstCommentID_0",
                "Discussion_FirstCommentID_1",
                "Discussion_FirstCommentID_2",
                "Discussion_FirstCommentID_3",
                "Discussion_LastCommentID_0",
                "Discussion_LastCommentID_1",
                "Discussion_LastCommentID_2",
                "Discussion_LastCommentID_3",
                "Discussion_DateLastComment_0",
                "Discussion_DateLastComment_1",
                "Discussion_DateLastComment_2",
                "Discussion_DateLastComment_3",
                "Discussion_LastCommentUserID_0",
                "Discussion_LastCommentUserID_1",
                "Discussion_LastCommentUserID_2",
                "Discussion_LastCommentUserID_3",
                "Discussion_Hot_0",
                "Discussion_Hot_1",
                "Discussion_Hot_2",
                "Discussion_Hot_3",
                "Conversation_CountMessages_0",
                "Conversation_CountMessages_1",
                "Conversation_CountParticipants_0",
                "Conversation_CountParticipants_1",
                "Conversation_FirstMessageID_0",
                "Conversation_FirstMessageID_1",
                "Conversation_LastMessageID_0",
                "Conversation_LastMessageID_1",
                "Conversation_DateUpdated_0",
                "Conversation_DateUpdated_1",
                "Conversation_UpdateUserID_0",
                "Conversation_UpdateUserID_1",
            ],
            $successIds
        );
    }

    /**
     *Test the Category Counts
     */
    public function testCategoryCounts()
    {
        $this->setDicussionCountTestData();
        $this->categoryModel->update(
            [
                "CountCategories" => 0,
                "CountDiscussions" => 0,
                "CountAllDiscussions" => 0,
                "CountComments" => 0,
                "CountAllComments" => 0,
                "LastCommentID" => null,
                "LastDiscussionID" => null,
                "LastDateInserted" => null,
                "CountFollowers" => 0,
            ],
            []
        );
        $body = [
            "aggregates" => ["category.*"],
        ];
        $response = $this->api()->put("/dba/recalculate-aggregates", $body);
        // Request should be successful.
        $this->assertEquals(200, $response->getStatusCode());

        $allCategoryIDs = $this->categoryModel
            ->createSql()
            ->select("CategoryID")
            ->from("Category")
            ->get()
            ->column("CategoryID");
        foreach ($allCategoryIDs as $categoryID) {
            $this->assertCategoryCounts($categoryID);
        }
    }

    /**
     * Test recalculating the nested comment fields -- i.e., depth, scoreChildComments, and countChildComments. This
     * test is run in batches of 2.
     *
     * @return void
     */
    public function testNestedCommentsRecalculation(): void
    {
        $this->insertCategories(1);
        $this->insertDiscussions(1);
        $topLevelComment = $this->insertComments(1, [
            "parentRecordType" => "discussion",
            "parentRecordID" => $this->lastInsertedDiscussionID,
        ]);
        $oneDeep = $this->insertComments(1, [
            "parentRecordType" => "discussion",
            "parentRecordID" => $this->lastInsertedDiscussionID,
            "parentCommentID" => $topLevelComment[0]["CommentID"],
            "Score" => 5,
        ]);
        $twoDeep = $this->insertComments(1, [
            "parentRecordType" => "discussion",
            "parentRecordID" => $this->lastInsertedDiscussionID,
            "parentCommentID" => $oneDeep[0]["CommentID"],
            "Score" => 5,
        ]);
        $threeDeep = $this->insertComments(2, [
            "parentRecordType" => "discussion",
            "parentRecordID" => $this->lastInsertedDiscussionID,
            "parentCommentID" => $twoDeep[0]["CommentID"],
            "Score" => 5,
        ]);

        $body = [
            "aggregates" => ["comment.*"],
        ];

        $response = $this->api()->put("/dba/recalculate-aggregates", $body);
        // Request should be successful.
        $this->assertEquals(200, $response->getStatusCode());

        // Verify that the correct values have been calculated for each recalculated field.
        $topLevelComment = $this->api()
            ->get("/comments/{$topLevelComment[0]["CommentID"]}")
            ->getBody();
        $this->assertSame(1, $topLevelComment["depth"]);
        $this->assertSame(20, $topLevelComment["scoreChildComments"]);
        $this->assertSame(4, $topLevelComment["countChildComments"]);

        $oneDeep = $this->api()
            ->get("/comments/{$oneDeep[0]["CommentID"]}")
            ->getBody();
        $this->assertSame(2, $oneDeep["depth"]);
        $this->assertSame(15, $oneDeep["scoreChildComments"]);
        $this->assertSame(3, $oneDeep["countChildComments"]);

        $twoDeep = $this->api()
            ->get("/comments/{$twoDeep[0]["CommentID"]}")
            ->getBody();
        $this->assertSame(3, $twoDeep["depth"]);
        $this->assertSame(10, $twoDeep["scoreChildComments"]);
        $this->assertSame(2, $twoDeep["countChildComments"]);

        $threeDeep_1 = $this->api()
            ->get("/comments/{$threeDeep[0]["CommentID"]}")
            ->getBody();
        $this->assertSame(4, $threeDeep_1["depth"]);
        $this->assertSame(0, $threeDeep_1["scoreChildComments"]);
        $this->assertSame(0, $threeDeep_1["countChildComments"]);

        $threeDeep_2 = $this->api()
            ->get("/comments/{$threeDeep[1]["CommentID"]}")
            ->getBody();
        $this->assertSame(4, $threeDeep_2["depth"]);
        $this->assertSame(0, $threeDeep_2["scoreChildComments"]);
        $this->assertSame(0, $threeDeep_2["countChildComments"]);
    }
}
