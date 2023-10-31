<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

use Garden\Container\ContainerException;
use Garden\Container\NotFoundException;

/**
 * Tests for the QnAFollowUpJob.
 */
class QnAFollowupJobTest extends \VanillaTests\SiteTestCase
{
    use \VanillaTests\UsersAndRolesApiTestTrait;
    use \VanillaTests\APIv2\QnaApiTestTrait;
    use \VanillaTests\EventSpyTestTrait;

    protected static $addons = ["qna"];

    public function setUp(): void
    {
        parent::setUp();
        $config = $this->container()->get(\Vanilla\Contracts\ConfigurationInterface::class);
        $config->saveToConfig(["QnA.FollowUp.Enabled" => true, "QnA.FollowUp.Interval" => 1]);
        Gdn::structure()
            ->table("Category")
            ->column("QnaFollowUpNotification", "tinyint(1)", ["Null" => false, "Default" => 1])
            ->set();
    }

    /**
     * Test the QnAFollowUpJob.
     *
     * @return void
     * @throws ContainerException
     * @throws NotFoundException
     */
    public function testQnAFollowUpJob(): void
    {
        $followUpCategory = $this->createCategory();

        // Make a user who wants to get qna follow-up emails.
        $user = $this->createUser();
        $this->api()->patch("/notification-preferences/{$user["userID"]}", ["QuestionFollowUp" => ["email" => true]]);

        // Set the time to 3 days ago (further back than our notification interval of 1 day).
        \Vanilla\CurrentTimeStamp::mockTime("-3 days");

        // Ask a question.
        $this->api()->setUserID($user["userID"]);
        $question = $this->createQuestion(["categoryID" => $followUpCategory["categoryID"]]);
        // Get an answer.
        $this->api()->setUserID(self::$siteInfo["adminUserID"]);
        $this->createAnswer();

        // Fast-forward to today.
        $timeNow = \Vanilla\CurrentTimeStamp::mockTime(now());

        // Run the job.
        $followUpJob = $this->container()->get(\Vanilla\QnA\Job\QnaFollowupJob::class);
        $followUpJob->run();

        // We have to get this from the model, since the field we're interested in doesn't come through in the API.
        $discussionModel = Gdn::getContainer()->get(DiscussionModel::class);
        $discussion = $discussionModel->getID($question["discussionID"]);

        // How do we know it worked? We save a notificationDate to a discussion's attributes column when we send a
        // QnAFollowUp email (for some reason unknown to me). This assertion shows that the field has been set
        // and, therefore, the email has been sent.
        $this->assertArrayHasKey("notificationDate", $discussion->Attributes);
        $this->assertSame($timeNow->getTimestamp(), strtotime($discussion->Attributes["notificationDate"]));
    }
}
