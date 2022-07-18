<?php
/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace VanillaTests\APIv2;

use Vanilla\Utility\ModelUtils;
use VanillaTests\ExpectExceptionTrait;
use VanillaTests\SchedulerTestTrait;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Tests to cover migration of question status
 * to new unified status.
 */
class QuestionStatusMigrationTest extends AbstractAPIv2Test
{
    use UsersAndRolesApiTestTrait;
    use SchedulerTestTrait;
    use ExpectExceptionTrait;
    use QnaApiTestTrait;

    /** @var string[] */
    public static $addons = ["qna"];
    protected static $inc = 1;

    /** @var int $categoryID associated with an idea category. */
    private static $categoryID;

    /** @var array $record */
    protected $record = [
        "name" => "Test Question",
        "body" => "Hello world!",
        "format" => "markdown",
    ];

    /** @var array $questions */
    protected $questions = [];

    /** @var string|null */
    private $sql;

    /**
     * {@inheritdoc}
     */
    public static function setupBeforeClass(): void
    {
        parent::setupBeforeClass();
        \Gdn::config()->set("Dba.Limit", 1);

        /** @var \CategoryModel $categoryModel */
        $categoryModel = self::container()->get("CategoryModel");

        static::$categoryID = $categoryModel->save([
            "Name" => "Test Question Category",
            "UrlCode" => "test-question-category",
            "InsertUserID" => self::$siteInfo["adminUserID"],
        ]);
        ModelUtils::validationResultToValidationException($categoryModel);
        self::enableCaching();
    }

    /**
     * {@inheritdoc}
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->resetTable("Discussion");
        $this->resetTable("Comment");
        $this->resetTable("Attachment");
        $discussionModel = \Gdn::getContainer()->get(\DiscussionModel::class);
        $this->sql = $discussionModel->SQL;
        for ($i = 1; $i <= 6; $i++) {
            $answerRecord = ["body" => "Answer for Question " . static::$inc];
            $question = $this->createQuestion($this->record());
            if ($i != 1) {
                $answerRecord["discussionID"] = $question["discussionID"];
                $answer = $this->createAnswer($answerRecord);
                if ($i % 2 == 0) {
                    $this->acceptAnswer($question, $answer);
                } else {
                    $this->rejectAnswer($answer);
                }
            }
            $this->questions[$question["discussionID"]] = $discussionModel->getID(
                $question["discussionID"],
                DATASET_TYPE_ARRAY
            );
            //We need to empty statusId so that it can be recalculated
            $this->sql
                ->update("Discussion", ["statusID" => 0], ["DiscussionID" => array_keys($this->questions)])
                ->put();

            //Verify the records got cleared
            $statusID = $this->sql
                ->select("statusID")
                ->where(["DiscussionID" => $question["discussionID"]])
                ->get("Discussion")
                ->column("statusID")[0];
            $this->assertEquals(0, $statusID);
        }
    }

    /**
     * question record
     */
    public function record()
    {
        $record = $this->record;
        $record["categoryID"] = static::$categoryID;
        $record["name"] .= " " . static::$inc++;

        return $record;
    }

    /**
     * Reject an answer
     *
     * @param array $answer
     * @return void
     */
    public function rejectAnswer(array $answer)
    {
        $response = $this->api()->patch("comments/answer/" . $answer["commentID"], [
            "status" => "rejected",
        ]);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertIsAnswer($response->getBody(), ["status" => "rejected"]);
    }

    /**
     * Check to for permission Error
     */
    public function testInvalidPermissions()
    {
        $memberID = $this->createUserFixture("Member");
        $this->runWithUser(function () {
            $this->expectExceptionCode(403);
            $this->api()->patch("/question-status/migrate");
        }, $memberID);
    }

    /**
     * Test the statuses are updated back successfully
     */
    public function testMigrateQuestionStatus()
    {
        $response = $this->api()->patch("/question-status/migrate");
        $this->assertEquals(200, $response->getStatusCode());
        $body = $response->getBody();
        $this->assertEquals(array_keys($this->questions), $body["progress"]["successIDs"]);
        $this->assertEquals(count($this->questions), $body["progress"]["countTotalIDs"]);
        $this->assertEmpty($body["progress"]["failedIDs"]);
        $updatedQuestionStatuses = $this->sql
            ->select(["DiscussionID", "statusID"])
            ->where(["Type" => "Question"])
            ->get("Discussion")
            ->resultArray();
        foreach ($updatedQuestionStatuses as $updatedStatus) {
            $this->assertEquals(
                $this->questions[$updatedStatus["DiscussionID"]]["statusID"],
                $updatedStatus["statusID"]
            );
        }
    }

    /**
     * Test to see we are getting a payload on timeouts
     */
    public function testForTimeout()
    {
        $this->getLongRunner()->setMaxIterations(1);
        $responseBody = $this->runWithExpectedException(\Garden\Web\Exception\ClientException::class, function () {
            $this->api()->patch("/question-status/migrate");
        });
        $this->assertIsArray($responseBody);
        $this->assertEquals(count($this->questions), $responseBody["progress"]["countTotalIDs"]);
        $this->assertCount(1, $responseBody["progress"]["successIDs"]);
        $this->assertNotNull($responseBody["callbackPayload"]);
    }
}
