<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

namespace VanillaTests\APIv2;

use CategoriesApiController;
use Garden\Web\Exception\ForbiddenException;
use Gdn_Session;
use Vanilla\CurrentTimeStamp;
use VanillaTests\ExpectedNotification;
use VanillaTests\NotificationsApiTestTrait;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Test managing answers with the /api/v2/comments endpoint.
 */
class CommentsAnswerTest extends AbstractAPIv2Test
{
    use QnaApiTestTrait;
    use UsersAndRolesApiTestTrait;
    use NotificationsApiTestTrait;

    public static $addons = ["qna"];
    private static $category;

    /**
     * {@inheritdoc}
     */
    public static function setupBeforeClass(): void
    {
        parent::setupBeforeClass();

        /**
         * @var Gdn_Session $session
         */
        $session = self::container()->get(Gdn_Session::class);
        $session->start(self::$siteInfo["adminUserID"], false, false);

        /** @var CategoriesApiController $categoryAPIController */
        $categoryAPIController = static::container()->get("CategoriesApiController");

        self::$category = $categoryAPIController->post([
            "name" => "answerTest",
            "urlcode" => "answertest",
        ]);

        $session->end();
    }

    /**
     * Test getting an answer.
     *
     * @depends testPostAnswer
     */
    public function testGetAnswer()
    {
        $answer = $this->testPostAnswer();
        $commentID = $answer["commentID"];

        $response = $this->api()->get("comments/{$commentID}");
        $this->assertEquals(200, $response->getStatusCode());

        $body = $response->getBody();
        $this->assertIsAnswer($body, ["status" => "pending"]);
    }

    /**
     * Test answer creation.
     *
     * @param int $discussionID If omitted the answer will be created on a new Question.
     * @return mixed
     */
    public function testPostAnswer($discussionID = null)
    {
        $category = $this->createCategory();
        if ($discussionID === null) {
            $question = $this->createQuestion(["categoryID" => $category["categoryID"]]);
            $discussionID = $question["discussionID"];
        }

        $record = [
            "discussionID" => $discussionID,
            "body" => "Hello world!",
            "format" => "markdown",
        ];
        $response = $this->api()->post("comments", $record);
        $this->assertEquals(201, $response->getStatusCode());

        $body = $response->getBody();

        $this->assertTrue(is_int($body["commentID"]));
        $this->assertTrue($body["commentID"] > 0);

        $this->assertRowsEqual($record, $body);

        $this->assertIsAnswer($body);

        $question = $this->getQuestion($discussionID);
        $this->assertIsQuestion($question, ["status" => "answered"]);

        return $body;
    }

    /**
     * Get a question.
     *
     * @param int $discussionID
     * @return array The question.
     */
    protected function getQuestion($discussionID)
    {
        $response = $this->api()->get("discussions/$discussionID");
        $this->assertEquals(200, $response->getStatusCode());

        return $response->getBody();
    }

    /**
     * Test accepting and then rejecting an answer.
     *
     * @depends testPostAnswer
     */
    public function testAcceptRejectAnswer()
    {
        $category = $this->createCategory();
        $question = $this->createQuestion(["categoryID" => $category["categoryID"]]);
        $answer = $this->testPostAnswer($question["discussionID"]);

        $response = $this->api()->patch("comments/answer/" . $answer["commentID"], [
            "status" => "accepted",
        ]);
        $this->assertEquals(200, $response->getStatusCode());
        $response = $this->api()->patch("comments/answer/" . $answer["commentID"], [
            "status" => "rejected",
        ]);
        $this->assertEquals(200, $response->getStatusCode());

        $this->assertIsAnswer($response->getBody(), ["status" => "rejected"]);

        $updatedQuestion = $this->getQuestion($question["discussionID"]);
        $this->assertIsQuestion($updatedQuestion, ["status" => "rejected"]);
    }

    /**
     * Test rejecting an answer and then resubmitting an answer.
     *
     * @depends testPostAnswer
     */
    public function testResetRejectedQuestionStatus()
    {
        $category = $this->createCategory();
        $question = $this->createQuestion(["categoryID" => $category["categoryID"]]);
        $answer = $this->testPostAnswer($question["discussionID"]);

        $response = $this->api()->patch("comments/answer/" . $answer["commentID"], [
            "status" => "rejected",
        ]);
        $this->assertEquals(200, $response->getStatusCode());

        $updatedQuestion = $this->getQuestion($question["discussionID"]);
        $this->assertIsQuestion($updatedQuestion, ["status" => "rejected"]);

        $answerAgain = $this->testPostAnswer($question["discussionID"]);
        $commentID = $answerAgain["commentID"];

        $response = $this->api()->get("comments/{$commentID}");
        $this->assertEquals(200, $response->getStatusCode());
        $body = $response->getBody();
        $this->assertIsAnswer($body, ["status" => "pending"]);

        $answeredQuestion = $this->getQuestion($question["discussionID"]);
        $this->assertIsQuestion($answeredQuestion, ["status" => "answered"]);
    }

    /**
     * Test dateAccepted and dateAnswered correspond with the accepted answer.
     *
     * @depends testPostAnswer
     */
    public function testAnsweredQuestionDates()
    {
        // Mock the current time.
        CurrentTimeStamp::mockTime("Dec 1 2010");
        $category = $this->createCategory();
        $question = $this->createQuestion(["categoryID" => $category["categoryID"]]);
        $this->assertIsQuestion($question, ["dateAccepted" => null]);

        $answer = $this->testPostAnswer($question["discussionID"]);

        $response = $this->api()->patch("comments/answer/" . $answer["commentID"], [
            "status" => "accepted",
        ]);
        $this->assertEquals(200, $response->getStatusCode());

        $body = $response->getBody();

        $answeredQuestion = $this->getQuestion($question["discussionID"]);
        $dateAnswered = $answeredQuestion["attributes"]["question"]["dateAnswered"];
        $this->assertEquals($answer["dateInserted"], $dateAnswered);
        $this->assertIsQuestion($answeredQuestion, ["dateAccepted" => $body["dateInserted"]]);
        $this->assertIsQuestion($answeredQuestion, ["dateAnswered" => $body["dateInserted"]]);
    }

    /**
     * Test dateAccepted and dateAnswered when answer is rejected.
     */
    public function testUnAnsweredQuestionDates()
    {
        $category = $this->createCategory();
        $question = $this->createQuestion(["categoryID" => $category["categoryID"]]);
        $this->assertIsQuestion($question, ["dateAccepted" => null]);

        $answerNumberOne = $this->testPostAnswer($question["discussionID"]);

        $response = $this->api()->patch("comments/answer/" . $answerNumberOne["commentID"], [
            "status" => "rejected",
        ]);
        $this->assertEquals(200, $response->getStatusCode());

        $unansweredQuestion = $this->getQuestion($question["discussionID"]);

        $this->assertIsQuestion($unansweredQuestion, ["dateAccepted" => null]);
        $this->assertIsQuestion($unansweredQuestion, ["dateAnswered" => null]);
    }

    /**
     * Test getting all unanswered questions as we do for the 'discussions/unanswered' endpoint.
     * This should get all (and only) questions with a status of 'unanswered' and 'rejected'.
     */
    public function testGetAllUnansweredQuestions()
    {
        $this->createQuestionSet();
        $unansweredQuestions = $this->bessy()
            ->get("discussions/unanswered")
            ->data("Discussions")
            ->resultArray();

        foreach ($unansweredQuestions as $question) {
            $this->assertContains((int) $question["statusID"], [
                \QnAPlugin::DISCUSSION_STATUS_UNANSWERED,
                \QnAPlugin::DISCUSSION_STATUS_REJECTED,
            ]);
        }
    }

    /**
     * Create a set of QnA discussions covering the range of answered statuses ('unanswered', 'answered', 'accepted', 'rejected').
     *
     * @return array The set of questions
     */
    public function createQuestionSet()
    {
        $category = $this->createCategory();
        $questionSet = [
            $this->createQuestion(["categoryID" => $category["categoryID"]]),
            $this->testResetAnswer(),
            $this->testAcceptAnswer(),
            $this->testRejectAnswer(),
        ];

        return $questionSet;
    }

    /**
     * Test accepting and then setting back an answer to pending.
     *
     * @return array Returns the updated question.
     * @depends testPostAnswer
     */
    public function testResetAnswer()
    {
        $category = $this->createCategory();
        $question = $this->createQuestion(["categoryID" => $category["categoryID"]]);
        $answer = $this->testPostAnswer($question["discussionID"]);

        $response = $this->api()->patch("comments/answer/" . $answer["commentID"], [
            "status" => "accepted",
        ]);
        $this->assertEquals(200, $response->getStatusCode());
        $response = $this->api()->patch("comments/answer/" . $answer["commentID"], [
            "status" => "pending",
        ]);
        $this->assertEquals(200, $response->getStatusCode());

        $this->assertIsAnswer($response->getBody(), ["status" => "pending"]);

        $updatedQuestion = $this->getQuestion($question["discussionID"]);
        $this->assertIsQuestion($updatedQuestion, ["status" => "answered"]);

        return $updatedQuestion;
    }

    /**
     * Test accepting an answer.
     *
     * @return array Returns the updated question.
     * @depends testPostAnswer
     */
    public function testAcceptAnswer()
    {
        $category = $this->createCategory();
        $question = $this->createQuestion(["categoryID" => $category["categoryID"]]);
        $answer = $this->testPostAnswer($question["discussionID"]);

        $response = $this->api()->patch("comments/answer/" . $answer["commentID"], [
            "status" => "accepted",
        ]);
        $this->assertEquals(200, $response->getStatusCode());

        $this->assertIsAnswer($response->getBody(), ["status" => "accepted"]);

        $updatedQuestion = $this->getQuestion($question["discussionID"]);
        $this->assertIsQuestion($updatedQuestion, ["status" => "accepted"]);

        return $updatedQuestion;
    }

    /**
     * Test rejecting an answer.
     *
     * @return array Returns the updated question.
     * @depends testPostAnswer
     */
    public function testRejectAnswer()
    {
        $category = $this->createCategory();
        $question = $this->createQuestion(["categoryID" => $category["categoryID"]]);
        $answer = $this->testPostAnswer($question["discussionID"]);

        $response = $this->api()->patch("comments/answer/" . $answer["commentID"], [
            "status" => "rejected",
        ]);
        $this->assertEquals(200, $response->getStatusCode());

        $this->assertIsAnswer($response->getBody(), ["status" => "rejected"]);

        $updatedQuestion = $this->getQuestion($question["discussionID"]);
        $this->assertIsQuestion($updatedQuestion, ["status" => "rejected"]);

        return $updatedQuestion;
    }

    /**
     * Test that a ForbiddenException is thrown when a member-level user tries to change the status of an answer on
     * a closed discussion
     */
    public function testAcceptAnswerOnClosedDiscussionAsMember(): void
    {
        $category = $this->createCategory();

        // Post a question as a member-level user.
        $memberUserID = $this->createUserFixture("Member");
        $this->api()->setUserID($memberUserID);
        $question = $this->createQuestion(["categoryID" => $category["categoryID"]]);

        // Add an answer and close the discussion.
        $this->api()->setUserID(self::$siteInfo["adminUserID"]);
        $answer = $this->api()
            ->post("comments", [
                "discussionID" => $question["discussionID"],
                "body" => "An exception will be thrown if a member-level user tried to change the status of this once the
                discussion has been closed.",
                "format" => "markdown",
            ])
            ->getBody();
        $this->api()->patch("discussions/{$question["discussionID"]}", ["closed" => true]);

        // Try to change the answer's status, but get an exception.
        $this->api()->setUserID($memberUserID);
        $this->expectExceptionObject(new ForbiddenException("Permission Problem"));
        $this->api()->patch("comments/answer/" . $answer["commentID"], [
            "status" => "accepted",
        ]);
    }

    /**
     * Test that an admin can still change the status on an answer when a discussion is closed.
     */
    public function testAcceptAnswerOnClosedDiscussionAsAdmin(): void
    {
        $category = $this->createCategory();
        $memberUserID = $this->createUserFixture("Member");

        // Post a QnA discussion and an answer.
        $this->api()->setUserID($memberUserID);
        $question = $this->createQuestion(["categoryID" => $category["categoryID"]]);
        $answer = $this->api()
            ->post("comments", [
                "discussionID" => $question["discussionID"],
                "body" => "An admin can change the status of this even when it's closed",
                "format" => "markdown",
            ])
            ->getBody();

        // Close the discussion.
        $this->api()->setUserID(self::$siteInfo["adminUserID"]);
        $this->api()->patch("discussions/{$question["discussionID"]}", ["closed" => true]);

        // Verify that the admin can still change an answer's status.
        $response = $this->api()->patch("comments/answer/" . $answer["commentID"], [
            "status" => "accepted",
        ]);
        $this->assertEquals(200, $response->getStatusCode());
        $updatedDiscussion = $this->getQuestion($question["discussionID"]);
        $status = $updatedDiscussion["attributes"]["question"]["status"];
        $this->assertSame("accepted", $status);
    }

    /**
     * Test that when a QnA discussion is closed, the option to accept or reject an answer is present for admins, but
     * not for a member-level user.
     */
    public function testQnACommentOptionsOnClosedDiscussionRoleDependent()
    {
        $apiUserId = $this->api()->getUserID();
        $category = $this->createCategory();
        $memberUserID = $this->createUserFixture("Member");

        // Post a question and an answer as a member-level user.
        $this->api()->setUserID($memberUserID);
        $question = $this->createQuestion(["categoryID" => $category["categoryID"]]);
        $answer = $this->api()
            ->post("comments", [
                "discussionID" => $question["discussionID"],
                "body" => "QnA options on this shouldn't be present when the discussion is closed.",
                "format" => "markdown",
            ])
            ->getBody();

        // Go to the discussion page and verify that the option to accept or reject an answer is added to the comment.
        $discussionData = $this->bessy()
            ->getHtml("discussion/{$question["discussionID"]}")
            ->getInnerHtml();
        $this->assertStringContainsString('class="DidThisAnswer"', $discussionData);

        // Restore API User ID
        $this->api()->setUserID($apiUserId);

        // Users with the Garden.Curation.Manage permission should see the option
        $this->runWithPermissions(
            function () use ($question) {
                $html = $this->bessy()
                    ->getHtml($question["url"])
                    ->getInnerHtml();
                $this->assertStringContainsString('class="DidThisAnswer"', $html);
            },
            ["curation.manage" => true],
            $this->categoryPermission(-1, ["discussions.view" => true])
        );

        // Close the discussion.
        $this->api()->setUserID(self::$siteInfo["adminUserID"]);
        $this->api()->patch("discussions/{$question["discussionID"]}", ["closed" => true]);

        // Now go back to the page and verify that the accept/reject option is no longer there for the member user.
        $this->api()->setUserID($memberUserID);
        $updatedDiscussionAccessedByMember = $this->bessy()
            ->getHtml("discussion/{$question["discussionID"]}")
            ->getInnerHtml();
        $this->assertStringNotContainsString('class="DidThisAnswer"', $updatedDiscussionAccessedByMember);

        // But the option is still there for the admin user.
        $this->api()->setUserID(self::$siteInfo["adminUserID"]);
        $updatedDiscussionAccessedByAdmin = $this->bessy()
            ->getHtml("discussion/{$question["discussionID"]}")
            ->getInnerHtml();
        $this->assertStringContainsString('class="DidThisAnswer"', $updatedDiscussionAccessedByAdmin);

        // Restore API User ID
        $this->api()->setUserID($apiUserId);

        // And the option is no longer there for users with Garden.Curation.Manage
        $this->runWithPermissions(
            function () use ($question) {
                $html = $this->bessy()
                    ->getHtml("discussion/{$question["discussionID"]}")
                    ->getInnerHtml();
                $this->assertStringNotContainsString('class="DidThisAnswer"', $html);
            },
            ["curation.manage" => true],
            $this->categoryPermission(-1, ["discussions.view" => true])
        );
    }

    /**
     * Test notifications from comments.
     */
    public function testAnswerNotifications()
    {
        $notifyUser = $this->createUser();
        $question = $this->runWithUser(function () {
            return $this->createQuestion();
        }, $notifyUser);

        $user = $this->createUser();
        $this->runWithUser(function () {
            $this->createAnswer([
                "body" => "check your notifications",
            ]);
        }, $user);

        // We should have a single notification with a singular headline.
        $this->assertUserHasNotificationsLike($notifyUser, [
            new ExpectedNotification("QuestionAnswer", [$user["name"], "answered your question", $question["name"]]),
        ]);

        $this->runWithUser(function () {
            $this->createAnswer([
                "body" => "check your notifications again",
            ]);
        }, $user);

        $this->api()->setUserID(self::$siteInfo["adminUserID"]);

        // We should have a single notification with a plural headline.
        $this->assertUserHasNotificationsLike($notifyUser, [
            new ExpectedNotification("QuestionAnswer", [
                "There are",
                "2",
                "new answers to your question",
                $question["name"],
            ]),
        ]);
    }
}
