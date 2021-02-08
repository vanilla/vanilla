<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace VanillaTests\APIv2;

use Garden\Web\Exception\NotFoundException;
use ReactionModel;

/**
 * Test {@link ReactionsPlugin} API capabilities.
 */
class ReactionsReactTest extends AbstractAPIv2Test {

    /** @var \LogModel */
    private $logModel;

    /**
     * Setup routine, run before each test case.
     */
    public function setUp(): void {
        parent::setUp();
        ReactionModel::$ReactionTypes = null;
        $this->logModel = self::container()->get(\LogModel::class);
    }

    /**
     * Setup routine, run before the test class is instantiated.
     */
    public static function setupBeforeClass(): void {
        self::$addons = ['reactions', 'stubcontent', 'vanilla'];
        parent::setUpBeforeClass();
    }

    /**
     * Test changing a user reaction from one type to another.
     */
    public function testChangeReaction() {
        $this->api()->post('/discussions/1/reactions', [
            'reactionType' => 'Like'
        ]);
        $reactions = $this->api()->get('/discussions/1/reactions');
        $this->assertTrue($this->hasUserReaction($this->api()->getUserID(), 'Like', $reactions->getBody()));
        $this->assertFalse($this->hasUserReaction($this->api()->getUserID(), 'LOL', $reactions->getBody()));

        $this->api()->post('/discussions/1/reactions', [
            'reactionType' => 'LOL'
        ]);
        $reactions = $this->api()->get('/discussions/1/reactions');
        $this->assertTrue($this->hasUserReaction($this->api()->getUserID(), 'LOL', $reactions->getBody()));
        $this->assertFalse($this->hasUserReaction($this->api()->getUserID(), 'Like', $reactions->getBody()));
    }

    /**
     * Test a user adding the same reaction to the same post, twice.
     */
    public function testDuplicateReaction() {
        $this->api()->post('/discussions/1/reactions', [
            'reactionType' => 'Like'
        ]);
        $summary = $this->api()->post('/discussions/1/reactions', [
            'reactionType' => 'Like'
        ]);

        $this->assertEquals(1, $this->getSummaryCount('Like', $summary->getBody()));

        $reactions = $this->api()->get('/discussions/1/reactions')->getBody();
        $currentUserReactions = 0;
        foreach ($reactions as $row) {
            if ($row['user']['userID'] == $this->api()->getUserID()) {
                $currentUserReactions++;
            }
        }
        $this->assertEquals(1, $currentUserReactions);
    }

    /**
     * Test reacting to a comment.
     */
    public function testPostCommentReaction() {
        $type = 'Like';
        $response = $this->api()->post('/comments/1/reactions', [
            'reactionType' => $type
        ]);
        $this->assertEquals(201, $response->getStatusCode());

        $body = $response->getBody();
        $this->assertIsArray($body);
        $this->assertSummaryHasReactionType($type, $body);
    }

    /**
     * Test getting reactions to a comment.
     *
     * @depends testPostCommentReaction
     */
    public function testGetCommentReactions() {
        $type = 'Like';
        $this->api()->post('/comments/1/reactions', [
            'reactionType' => $type
        ]);

        $response = $this->api()->get('/comments/1/reactions');
        $body = $response->getBody();
        $this->assertIsArray($body);
        $this->assertNotEmpty($body);
        $this->asserttrue($this->hasUserReaction($this->api()->getUserID(), $type, $body));
    }

    /**
     * Test undoing a reaction to a comment.
     *
     * @depends testGetCommentReactions
     */
    public function testDeleteCommentReaction() {
        $type = 'Like';

        $user = $this->createReactionsTestUser(rand(1, 100000));
        $userID = (int)$user['userID'];
        $this->api()->setUserID($userID);

        $this->api()->post('/comments/1/reactions', [
            'reactionType' => $type
        ]);

        $postResponse = $this->api()->get('/comments/1/reactions');
        $this->assertTrue($this->hasUserReaction($this->api()->getUserID(), $type, $postResponse->getBody()));

        $this->api()->delete("/comments/1/reactions/{$userID}");
        $response = $this->api()->get('/comments/1/reactions');
        $this->assertFalse($this->hasUserReaction($this->api()->getUserID(), $type, $response->getBody()));
    }

    /**
     * Test ability to expand reactions on a comment.
     */
    public function testExpandComment() {
        $getResponse = $this->api()->get('/comments/1', ['expand' => 'reactions']);
        $getBody = $getResponse->getBody();
        $this->assertTrue($this->isReactionSummary($getBody['reactions']));

        $indexResponse = $this->api()->get('/comments', [
            'discussionID' => 1,
            'expand' => 'reactions'
        ]);
        $indexBody = $indexResponse->getBody();
        $indexHasReactions = true;
        foreach ($indexBody as $row) {
            $indexHasReactions = $indexHasReactions && $this->isReactionSummary($row['reactions']);
            if ($indexHasReactions === false) {
                break;
            }
        }
        $this->assertTrue($indexHasReactions);
    }

    /**
     * Test reacting to a discussion.
     */
    public function testPostDiscussionReaction() {
        $type = 'Like';
        $response = $this->api()->post('/discussions/1/reactions', [
            'reactionType' => $type
        ]);
        $this->assertEquals(201, $response->getStatusCode());

        $body = $response->getBody();
        $this->assertIsArray($body);
        $this->assertSummaryHasReactionType($type, $body);
    }

    /**
     * Test marking a discussion as spam.
     */
    public function testPostDiscussionSpamReaction(): void {
        $type = 'Spam';
        // Create member user.
        $memberUser = $this->createReactionsTestUser('spamReactionUser');

        // Set api user to a member role.
        $this->api()->setUserID($memberUser['userID']);

        // Create test discussions as a member.
        $discussionA = $this->createDiscussion(1, 'Test DiscussionA');
        $discussionB = $this->createDiscussion(1, 'Test DiscussionB');

        $discussionIDA = $discussionA['discussionID'];
        $discussionIDB = $discussionB['discussionID'];
        // Switch api user to admin.
        $this->api()->setUserID(self::$siteInfo['adminUserID']);
        // Admin marks post as spam, post should be deleted, and moved to the log
        try {
            $this->api()->post("/discussions/${discussionIDA}/reactions", [
                'reactionType' => $type
            ]);
        } catch (NotFoundException $e) {
            $statusFailed = $e->getCode();
            $this->assertEquals('404', $statusFailed);
        }
        $logCountA = $this->logModel->getCountWhere(['Operation' => 'Spam', 'RecordUserID' => $memberUser['userID']]);
        $this->assertEquals(1, $logCountA);
        // Set api user back to member.
        $this->api()->setUserID($memberUser['userID']);
        // Member creates 10 comments.
        $this->generateComments(10, $discussionIDB);
        // Switch api back to admin user.
        $this->api()->setUserID(self::$siteInfo['adminUserID']);
        // Moderator marks post as Spam, Post doesn't get deleted (>= 10 comments).
        $response = $this->api()->post("/discussions/${discussionIDB}/reactions", [
            'reactionType' => $type
        ]);
        $statusSuccess = $response->getStatusCode();
        $this->assertEquals('201', $statusSuccess);
        $logCountB = $this->logModel->getCountWhere(['Operation' => 'Spam', 'RecordUserID' => $memberUser['userID']]);
        $this->assertEquals(2, $logCountB);
    }


    /**
     * Create discussion.
     *
     * @param int $categoryID Number of Comments to generate.
     * @param string $name Discussion name.
     *
     * @return array $discussion
     */
    private function createDiscussion(int $categoryID, string $name): array {
        // Member creates a discussion.
        $discussion = $this->api()->post('discussions', [
            "categoryID" => $categoryID,
            "name" => $name,
            "body" => "Hello world!",
            "format" => "Markdown"
        ])->getBody();
        return $discussion;
    }

    /**
     * Generate comments
     *
     * @param int $counter Number of Comments to generate.
     * @param int $discussionID DiscussionID.
     */
    private function generateComments(int $counter, int $discussionID): void {
        for ($i = 0; $i < $counter; $i++) {
            $this->api()->post("/comments", [
                "body" => "test comment".$i,
                "format" => "Markdown",
                "discussionID" => $discussionID
            ]);
        }
    }

    /**
     * Test getting reactions to a discussion.
     *
     * @depends testPostDiscussionReaction
     */
    public function testGetDiscussionReactions() {
        $type = 'Like';
        $this->api()->post('/discussions/1/reactions', [
            'reactionType' => $type
        ]);

        $response = $this->api()->get('/discussions/1/reactions');
        $body = $response->getBody();
        $this->assertIsArray($body);
        $this->assertNotEmpty($body);
        $this->assertTrue($this->hasUserReaction($this->api()->getUserID(), $type, $body));
    }

    /**
     * Test the discussions index filtering by user and reaction type.
     */
    public function testGetDiscussionsByUserReaction() {
        $type = 'Like';

        $discussion1 = $this->createDiscussion(1, 'testGetDiscussionsByUserReaction');
        $discussion2 = $this->createDiscussion(1, 'testGetDiscussionsByUserReactionPart2');

        $user = $this->createReactionsTestUser(rand(1, 1000000));
        \Gdn::session()->start($user['userID']);

        $this->api()->post("/discussions/${discussion1['discussionID']}/reactions", [
            'reactionType' => $type
        ]);
        $this->api()->post("/discussions/${discussion2['discussionID']}/reactions", [
            'reactionType' => $type
        ]);

        $newLikedDiscussions = $this->api()->get("/discussions?reactionType=${type}&expand=reactions")->getBody();

        $this->assertEquals(2, count($newLikedDiscussions));
    }

    /**
     * Test undoing a reaction to a discussion.
     *
     * @depends testGetCommentReactions
     */
    public function testDeleteDiscussionReaction() {
        $type = 'Like';
        $user = $this->createReactionsTestUser(rand(1, 100000));
        $userID = (int)$user['userID'];
        $this->api()->setUserID($userID);

        $this->api()->post('/discussions/1/reactions', [
            'reactionType' => $type
        ]);
        $postResponse = $this->api()->get('/discussions/1/reactions');
        $this->assertTrue($this->hasUserReaction($this->api()->getUserID(), $type, $postResponse->getBody()));
        $this->api()->delete("/discussions/1/reactions/{$userID}");
        $response = $this->api()->get('/discussions/1/reactions');
        $this->assertFalse($this->hasUserReaction($this->api()->getUserID(), $type, $response->getBody()));
    }

    /**
     * Test ability to expand reactions on a discussion.
     */
    public function testExpandDiscussion() {
        $getResponse = $this->api()->get('/discussions/1', ['expand' => 'reactions']);
        $getBody = $getResponse->getBody();
        $this->assertTrue($this->isReactionSummary($getBody['reactions']));
        $indexResponse = $this->api()->get('/discussions', ['expand' => 'reactions']);
        $indexBody = $indexResponse->getBody();
        $indexHasReactions = true;
        foreach ($indexBody as $row) {
            $indexHasReactions = $indexHasReactions && $this->isReactionSummary($row['reactions']);
            if ($indexHasReactions === false) {
                break;
            }
        }
        $this->assertTrue($indexHasReactions);
    }

    /**
     * Get the count for a type from a summary array.
     *
     * @param string $type The URL code of a type.
     * @param array $summary A summary of reactions on a record.
     * @return int
     */
    public function getSummaryCount($type, array $summary) {
        $result = 0;

        foreach ($summary as $row) {
            if ($row['urlcode'] === $type) {
                $result = $row['count'];
                break;
            }
        }

        return $result;
    }

    /**
     * Given a user ID and a reaction type, verify the combination is in a log of reactions.
     *
     * @param int $userID
     * @param string $type
     * @param array $data
     * @return bool
     */
    public function hasUserReaction($userID, $type, array $data) {
        $result = false;

        foreach ($data as $row) {
            if (!array_key_exists('userID', $row) || $row['userID'] !== $userID) {
                continue;
            } elseif (!array_key_exists('reactionType', $row) || !is_array($row['reactionType'])) {
                continue;
            } elseif (!array_key_exists('urlcode', $row['reactionType']) || $row['reactionType']['urlcode'] !== $type) {
                continue;
            } else {
                $result = true;
                break;
            }
        }

        return $result;
    }

    /**
     * Is the data collection a valid reaction summary?
     *
     * @param array $data
     * @return bool
     */
    public function isReactionSummary(array $data) {
        $result = true;

        foreach ($data as $row) {
            if (!array_key_exists('tagID', $row) || !is_int($row['tagID']) ||
                !array_key_exists('urlcode', $row) || !is_string($row['urlcode']) ||
                !array_key_exists('name', $row) || !is_string($row['name']) ||
                !array_key_exists('class', $row) || !is_string($row['class']) ||
                !array_key_exists('count', $row) || !is_int($row['count'])) {

                $result = false;
                break;
            }
        }

        return $result;
    }

    /**
     * Assert a reaction summary contains a greater-than-zero number of a particular reaction type.
     *
     * @param string $type A valid URL code for a reaction type.
     * @param array $data Data collection (e.g. a response body).
     */
    public function assertSummaryHasReactionType($type, array $data) {
        $result = false;

        foreach ($data as $row) {
            if (!array_key_exists('urlcode', $row) || !array_key_exists('count', $row)) {
                continue;
            } elseif ($row['urlcode'] !== $type) {
                continue;
            }

            if ($row['count'] > 0) {
                $result = true;
            }
            break;
        }

        $this->assertTrue($result, "Unable to find a greater-than-zero count for reaction type: {$type}");
    }

    /**
     * Create a default user to test reacting.
     *
     * @param string $uid unique identifier.
     *
     * @return array $user newly created user.
     */
    protected function createReactionsTestUser(string $uid): array {
        // Create a new user for this test. It will receive the default member role.
        $username = substr(__FUNCTION__, 0, 20);
        $user = $this->api()->post('users', [
            'name' => $username . $uid,
            'email' => $username .$uid . '@example.com',
            'password' => 'vanilla'
        ])->getBody();

        return $user;
    }
}
