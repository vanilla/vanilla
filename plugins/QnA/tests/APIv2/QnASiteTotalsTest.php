<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

use QnAPlugin;
use Vanilla\CurrentTimeStamp;
use Vanilla\Dashboard\Models\RecordStatusModel;

/**
 * Test for site total counts that depend on QnA.
 */
class QnASiteTotalsTest extends AbstractAPIv2Test
{
    use QnaApiTestTrait;

    // Don't enable stub content.
    public static $addons = ["vanilla", "qna"];

    protected $baseUrl = "/site-totals";

    protected $cache;

    /**
     * @inheritDoc
     */
    public function setUp(): void
    {
        parent::setUp();
        // Make sure we have a fresh cache for each test.
        self::$testCache->flush();
    }

    /**
     * Create one question with each status.
     */
    public function createQuestionsWithStatuses()
    {
        // Unanswered
        $this->createQuestion();

        // Answered
        $this->createQuestion();
        $this->createAnswer();

        // Accepted
        $question = $this->createQuestion();
        $answer = $this->createAnswer();
        $this->acceptAnswer($question, $answer);

        $questions = $this->api()
            ->get("discussions", ["type" => "question"])
            ->getBody();
        $returnedStatuses = array_column($questions, "statusID");
        $availableStatuses = [
            QnAPlugin::DISCUSSION_STATUS_UNANSWERED,
            QnAPlugin::DISCUSSION_STATUS_ANSWERED,
            QnAPlugin::DISCUSSION_STATUS_ACCEPTED,
        ];
        $this->assertEqualsCanonicalizing($returnedStatuses, $availableStatuses);
    }

    /**
     * Test that the returned question count is accurate.
     */
    public function testQuestionCount()
    {
        $this->createQuestionsWithStatuses();
        $siteTotalsQuestion = $this->api->get($this->baseUrl . "?counts[]=question")->getBody();
        $this->assertSame($siteTotalsQuestion["counts"]["question"]["count"], 3);
    }

    /**
     * Test that the returned "accepted" count is accurate.
     *
     * @depends testQuestionCount
     */
    public function testAcceptedCount()
    {
        $siteTotalsAccepted = $this->api->get($this->baseUrl . "?counts[]=accepted")->getBody();
        $this->assertSame($siteTotalsAccepted["counts"]["accepted"]["count"], 1);
    }
}
