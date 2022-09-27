<?php
/**
 * @author Raphaël Bergina <rbergina@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\QnA;

use CommentModel;
use DiscussionModel;
use Garden\Container\ContainerException;
use Garden\Container\NotFoundException;
use QnAPlugin;
use Vanilla\QnA\Models\AnswerModel;
use Vanilla\Web\AbstractJsonLDItem;
use VanillaTests\APIv2\QnaApiTestTrait;
use VanillaTests\SiteTestCase;

/**
 * Test QnA page structured data.
 */
class QnAStructuredDataTest extends SiteTestCase
{
    use QnaApiTestTrait;

    public static $addons = ["qna"];

    /** @var int */
    private $categoryID;

    /** @var DiscussionModel */
    private $discussionModel;

    /** @var CommentModel */
    private $commentModel;

    /** @var AnswerModel */
    private $answerModel;

    /**
     * Instantiate fixtures.
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->categoryID = $this->createCategory()["categoryID"];

        $this->container()->call(function (
            DiscussionModel $discussionModel,
            CommentModel $commentModel,
            AnswerModel $answerModel
        ) {
            $this->discussionModel = $discussionModel;
            $this->commentModel = $commentModel;
            $this->answerModel = $answerModel;
        });
    }

    /**
     * Tests we have QAPage structured data in the response.
     */
    public function testQnaPageStructuredData()
    {
        $question = $this->createQuestion([
            "categoryID" => $this->categoryID,
            "name" => "Question 1",
            "body" => "Question 1",
        ]);

        $this->createAnswer([
            "discussionID" => $question["discussionID"],
            "name" => "Answer 1",
            "body" => "Answer 1",
        ]);

        $answer2 = $this->createAnswer([
            "discussionID" => $question["discussionID"],
            "name" => "Answer 2",
            "body" => "Answer 2",
        ]);
        $this->createAnswer([
            "discussionID" => $question["discussionID"],
            "name" => "Answer 3",
            "body" => "Answer 3",
        ]);
        $answer4 = $this->createAnswer([
            "discussionID" => $question["discussionID"],
            "name" => "Answer 4",
            "body" => "Answer 4",
        ]);

        $this->answerModel->updateCommentQnA($question, $answer2, "Accepted");
        $this->answerModel->updateCommentQnA($question, $answer4, "Accepted");

        $this->commentModel->setField($answer4["commentID"], "Score", 1234);

        $this->recalculateDiscussionQnA($question);

        $response = $this->bessy()->get("/discussion/" . $question["discussionID"]);
        $jsonLDItems = array_map(function (AbstractJsonLDItem $item) {
            return $item->calculateValue()->getData();
        }, $response->Head->getJsonLDItems());
        $schemaTypes = array_column($jsonLDItems, "@type");
        $index = array_keys($schemaTypes, "QAPage");
        $this->assertContains("QAPage", $schemaTypes);
        $answerJson = $jsonLDItems[$index[0]];
        $this->assertCount(1, $index);
        $this->assertCount(2, $answerJson["mainEntity"]["acceptedAnswer"]);

        $found = false;
        foreach ($answerJson["mainEntity"]["acceptedAnswer"] as $answer) {
            $this->assertArrayHasKey("upvoteCount", $answer);
            if ($answer["url"] === $answer4["url"]) {
                $found = true;
                $this->assertSame(1234, $answer["upvoteCount"]);
            }
        }
        $this->assertTrue($found, "Answer 4 was not found in the JSON LD.");
    }

    /**
     * Test question's status when adding/deleting/accepting/rejecting responses.
     *
     * @param array $answersData
     * @param int $expectedFinalDiscussionStatus
     * @return void
     * @throws ContainerException
     * @throws NotFoundException
     *
     * @dataProvider getAnswersCheckData
     */
    public function testQuestionStatusDependingOnResponses(array $answersData, int $expectedFinalDiscussionStatus)
    {
        // Create a question.
        $question = $this->createQuestion(["categoryID" => $this->categoryID]);
        foreach ($answersData as $answerData) {
            // We create an answer to the question.
            $answer = $this->createAnswer(["discussionID" => $question["discussionID"]]);
            // If we want to assign a status to the answer, we do this here.
            if ($answerData["answerStatus"] ?? false) {
                $this->setAnswerStatus($question, $answer, $answerData["answerStatus"]);
            }
            // If we want to do a check on the discussion status, we do this here.
            if ($answerData["expectedDiscussionStatus"] ?? false) {
                $body = $this->api()
                    ->get("discussions/{$question["discussionID"]}")
                    ->getBody();
                $this->assertSame($answerData["expectedDiscussionStatus"], $body["statusID"]);
            }
            // If we want to delete the newly added answer, we do this here.
            if ($answerData["deleteAnswer"] ?? false) {
                $this->commentModel->deleteID($answer["commentID"]);
            }
        }
        // Assert that the discussion's status is as _finally_ expected.
        $body = $this->api()
            ->get("discussions/{$question["discussionID"]}")
            ->getBody();
        $this->assertSame($expectedFinalDiscussionStatus, $body["statusID"]);
    }

    /**
     * Data provider for testQuestionStatusDependingOnResponses().
     * @return array
     */
    public function getAnswersCheckData(): array
    {
        $result = [
            "No answer. Discussion is unanswered." => [[], QnAPlugin::DISCUSSION_STATUS_UNANSWERED],
            "1 answer. Discussion is answered." => [[[]], QnAPlugin::DISCUSSION_STATUS_ANSWERED],
            "1 answer is accepted. Discussion has an accepted answer." => [
                [["answerStatus" => "Accepted"]],
                QnAPlugin::DISCUSSION_STATUS_ACCEPTED,
            ],
            "1 answer is accepted, then deleted. Discussion remains unanswered." => [
                [
                    [
                        "answerStatus" => "Accepted",
                        "expectedDiscussionStatus" => QnAPlugin::DISCUSSION_STATUS_ACCEPTED,
                        "deleteAnswer" => true,
                    ],
                ],
                QnAPlugin::DISCUSSION_STATUS_UNANSWERED,
            ],
            "1 answer is rejected. Discussion remains unanswered." => [
                [["answerStatus" => "Rejected"]],
                QnAPlugin::DISCUSSION_STATUS_UNANSWERED,
            ],
            "1 answer is rejected, then deleted. Discussion remains unanswered." => [
                [
                    [
                        "answerStatus" => "Rejected",
                        "expectedDiscussionStatus" => QnAPlugin::DISCUSSION_STATUS_UNANSWERED,
                        "deleteAnswer" => true,
                    ],
                ],
                QnAPlugin::DISCUSSION_STATUS_UNANSWERED,
            ],
            "1 answer is accepted. 1 answer is rejected. Discussion has an accepted answer." => [
                [
                    ["answerStatus" => "Accepted", "expectedDiscussionStatus" => QnAPlugin::DISCUSSION_STATUS_ACCEPTED],
                    ["answerStatus" => "Rejected"],
                ],
                QnAPlugin::DISCUSSION_STATUS_ACCEPTED,
            ],
            "1 answer is rejected. 1 answer is accepted, then deleted. The discussion status is `Rejected`." => [
                [
                    [
                        "answerStatus" => "Rejected",
                        "expectedDiscussionStatus" => QnAPlugin::DISCUSSION_STATUS_UNANSWERED,
                    ],
                    [
                        "answerStatus" => "Accepted",
                        "expectedDiscussionStatus" => QnAPlugin::DISCUSSION_STATUS_ACCEPTED,
                        "deleteAnswer" => true,
                    ],
                ],
                QnAPlugin::DISCUSSION_STATUS_REJECTED,
            ],
            "2 answers are added, then deleted. The discussion remains unanswered." => [
                [
                    [
                        "expectedDiscussionStatus" => QnAPlugin::DISCUSSION_STATUS_ANSWERED,
                        "deleteAnswer" => true,
                    ],
                    [
                        "deleteAnswer" => true,
                    ],
                ],
                QnAPlugin::DISCUSSION_STATUS_UNANSWERED,
            ],
        ];

        return $result;
    }
}
