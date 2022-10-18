<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

namespace VanillaTests\APIv2;

use QnAPlugin;
use Vanilla\QnA\Models\AnswerModel;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;

/**
 * Trait QnaTestHelperTrait
 */
trait QnaApiTestTrait
{
    use CommunityApiTestTrait;

    /** @var int|null */
    private $lastInsertedQuestionID = null;

    /** @var int|null */
    private $lastInsertedAnswerID = null;

    private $validQuestionDiscussionStatusIDs = [
        QnAPlugin::DISCUSSION_STATUS_UNANSWERED,
        QnAPlugin::DISCUSSION_STATUS_ANSWERED,
        QnAPlugin::DISCUSSION_STATUS_ACCEPTED,
        QnAPlugin::DISCUSSION_STATUS_REJECTED,
    ];

    /**
     * Setup.
     */
    public function setUpQnaApiTestTrait()
    {
        $this->lastInsertedQuestionID = null;
        $this->lastInsertedAnswerID = null;
        \ActivityModel::$ActivityTypes = null;
    }

    /**
     * Create a Question.
     *
     * @param array $overrides
     * @return array
     */
    public function createQuestion(array $overrides = []): array
    {
        $categoryID = $overrides["categoryID"] ?? ($this->lastInsertedCategoryID ?? -1);
        $question = $this->api()
            ->post(
                "discussions/question",
                $overrides + [
                    "categoryID" => $categoryID,
                    "name" => "Question",
                    "body" => "Question being asked!",
                    "format" => "markdown",
                ]
            )
            ->getBody();
        $this->lastInsertedQuestionID = $this->lastInsertedDiscussionID = $question["discussionID"];
        return $question;
    }

    /**
     * Create an Answer.
     *
     * @param array $overrides
     * @return array
     */
    public function createAnswer(array $overrides = []): array
    {
        $questionID = $overrides["discussionID"] ?? $this->lastInsertedQuestionID;
        if ($questionID === null) {
            throw new \Exception("Could not insert a test answer because no question was specified.");
        }
        $record = $overrides + [
            "discussionID" => $questionID,
            "body" => "Answering",
            "format" => "markdown",
        ];
        $answer = $this->api()
            ->post("comments", $record)
            ->getBody();
        $this->lastInsertedAnswerID = $this->lastInsertCommentID = $answer["commentID"];
        return $answer;
    }

    /**
     * Assert an array has all necessary question fields.
     *
     * @param array $discussion
     * @param array $expectedAttributes
     */
    protected function assertIsQuestion($discussion, $expectedAttributes = [])
    {
        $this->assertIsArray($discussion);

        $this->assertArrayHasKey("type", $discussion);
        $this->assertEquals("question", $discussion["type"]);

        $this->assertArrayHasKey("attributes", $discussion);
        $this->assertArrayHasKey("question", $discussion["attributes"]);

        $this->assertArrayHasKey("status", $discussion["attributes"]["question"]);
        $this->assertArrayHasKey("dateAccepted", $discussion["attributes"]["question"]);
        $this->assertArrayHasKey("dateAnswered", $discussion["attributes"]["question"]);

        $this->assertContains($discussion["statusID"], $this->validQuestionDiscussionStatusIDs);

        foreach ($expectedAttributes as $attribute => $value) {
            $this->assertEquals($value, $discussion["attributes"]["question"][$attribute]);
        }
    }

    /**
     * Assert an array has all necessary answer fields.
     *
     * @param array $comment
     * @param array $expectedAttributes
     */
    protected function assertIsAnswer($comment, $expectedAttributes = [])
    {
        $this->assertIsArray($comment);

        $this->assertArrayHasKey("attributes", $comment);
        $this->assertArrayHasKey("answer", $comment["attributes"]);

        $this->assertArrayHasKey("status", $comment["attributes"]["answer"]);
        $this->assertArrayHasKey("dateAccepted", $comment["attributes"]["answer"]);
        $this->assertArrayHasKey("acceptUserID", $comment["attributes"]["answer"]);

        foreach ($expectedAttributes as $attribute => $value) {
            $this->assertEquals($value, $comment["attributes"]["answer"][$attribute]);
        }
    }

    /**
     * Set an answer's status.
     * @param array $question
     * @param array $answer
     * @param string $newQnA
     * @return void
     * @throws \Garden\Container\ContainerException
     * @throws \Garden\Container\NotFoundException
     */
    public function setAnswerStatus(array $question, array $answer, string $newQnA)
    {
        /** @var AnswerModel $model */
        $model = \Gdn::getContainer()->get(AnswerModel::class);
        $model->updateCommentQnA($question, $answer, $newQnA);
        $this->recalculateDiscussionQnA($question);
    }

    /**
     * Accept an answer.
     *
     * @param array $question
     * @param array $answer
     */
    public function acceptAnswer(array $question, array $answer)
    {
        $this->setAnswerStatus($question, $answer, "Accepted");
    }

    /**
     * Recalculate the QnA status of a discussion.
     *
     * @param array $question
     */
    public function recalculateDiscussionQnA(array $question)
    {
        /** @var \QnAPlugin $plugin */
        $plugin = \Gdn::getContainer()->get(\QnAPlugin::class);
        $plugin->recalculateDiscussionQnA($question);
    }

    /**
     * @return \QnaModel
     */
    private function getQnaModel(): \QnaModel
    {
        return self::container()->get(\QnaModel::class);
    }
}
