<?php
/**
 * @author David Barbier <dbarbier@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\AutomationRules\Tests\Triggers;

use Vanilla\AutomationRules\Actions\BumpDiscussionAction;
use Vanilla\AutomationRules\Triggers\DiscussionReachesScoreTrigger;
use Vanilla\Dashboard\Models\AutomationRuleModel;
use VanillaTests\APIv2\AbstractAPIv2Test;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\UsersAndRolesApiTestTrait;

class DiscussionReachesScoreTriggerTest extends AbstractAPIv2Test
{
    use CommunityApiTestTrait;
    use UsersAndRolesApiTestTrait;

    public static int $categoryID = 0;

    const AUTOMATION_URI = "/automation-rules";
    const DISCUSSION_URI = "/discussions";
    const REACTION_URI = "/discussions/%d/reactions";

    public function setUp(): void
    {
        parent::setUp();
        $this->createUserFixtures();
        if (!self::$categoryID) {
            $this->createCategory([
                "name" => "discussionScoreTest",
                "allowedDiscussionTypes" => ["Discussion"],
            ]);
            self::$categoryID = $this->lastInsertedCategoryID;
        }
    }

    /**
     * Test that the trigger validation works
     *
     * @return void
     */
    public function testTriggerValidation()
    {
        $automationRecord = self::getAutomationRecord(-3);
        $this->expectExceptionMessage("Points should be positive whole numbers greater than 0.");
        $this->api()->post(self::AUTOMATION_URI, $automationRecord);

        $automationRecord["trigger"]["triggerValue"]["score"] = 1.5;
        $this->expectExceptionMessage("trigger.triggerValue.score is not a valid integer.");
        $this->api()->post(self::AUTOMATION_URI, $automationRecord);

        $automationRecord["trigger"]["triggerValue"]["score"] = "";
        $this->expectExceptionMessage("trigger.triggerValue.score is not a valid integer.");
        $this->api()->post(self::AUTOMATION_URI, $automationRecord);
    }

    /**
     * Create an automation record for discussion score trigger
     *
     * @param int $score
     * @return array
     */
    public static function getAutomationRecord(int $score): array
    {
        return [
            "name" => "Discussion Score Trigger - " . strtotime("now"),
            "trigger" => [
                "triggerType" => DiscussionReachesScoreTrigger::getType(),
                "triggerValue" => [
                    "postType" => ["discussion"],
                    "score" => $score,
                ],
            ],
            "action" => [
                "actionType" => BumpDiscussionAction::getType(),
            ],
        ];
    }

    /**
     * Test that the record counts to process is correct
     */
    public function testGetRecordCountsToProcess()
    {
        $discussions = [];
        $name = "Test Discussion - %d";
        $fields = [
            "categoryID" => self::$categoryID,
            "body" => "Discussion's body",
            "format" => "markdown",
        ];
        for ($i = 1; $i <= 2; $i++) {
            $fields["name"] = sprintf($name, $i);
            $discussions[] = $this->createDiscussion($fields);
        }
        $id = $discussions[0]["discussionID"];

        $this->runWithUser(function () use ($id) {
            $this->api()->post(sprintft(self::REACTION_URI, $id), ["reactionType" => "up"]);
        }, $this->adminID);

        $this->runWithUser(function () use ($id) {
            $this->api()->post(sprintft(self::REACTION_URI, $id), ["reactionType" => "up"]);
        }, $this->memberID);

        $updatedDiscussion = $this->api()
            ->get("/discussions", ["discussionID" => $id])
            ->getBody();

        $this->assertEquals(2, $updatedDiscussion[0]["score"]);
        $drptTrigger = new DiscussionReachesScoreTrigger();
        $this->assertEquals(1, $drptTrigger->getRecordCountsToProcess(["Type" => ["discussion"], "score" => 2]));
    }

    /**
     * Test that the trigger works
     */
    public function testDiscussionReachesScoreTrigger()
    {
        // Create a discussion
        $record = [
            "categoryID" => self::$categoryID,
            "name" => "What an awesome Discussion",
            "body" => "Test discussion's body",
            "format" => "markdown",
        ];
        $discussion = $this->createDiscussion($record);

        // Create an automation rule
        $automationRecord = self::getAutomationRecord(2);
        $automationRecord["status"] = AutomationRuleModel::STATUS_ACTIVE;
        $automationRule = $this->createAutomationRule($automationRecord);

        $this->runWithUser(function () use ($discussion) {
            $this->api()->post(sprintft(self::REACTION_URI, $discussion["discussionID"]), ["reactionType" => "up"]);
        }, $this->memberID);

        $this->runWithUser(function () use ($discussion) {
            $this->api()->post(sprintft(self::REACTION_URI, $discussion["discussionID"]), ["reactionType" => "up"]);
        }, $this->moderatorID);

        // Check the log for the bump discussion action.
        $logModel = $this->container()->get(\LogModel::class);
        $eventLogs = $logModel->getWhere([
            "Operation" => "Automation",
            "RecordType" => "Discussion",
            "RecordID" => $discussion["discussionID"],
            "AutomationRuleRevisionID" => $automationRule["automationRuleRevisionID"],
        ]);
        $this->assertCount(1, $eventLogs);

        // Make sure a trigger is not executed again for the same record
        $this->runWithUser(function () use ($discussion) {
            $this->api()->post(sprintft(self::REACTION_URI, $discussion["discussionID"]), ["reactionType" => "up"]);
        }, $this->adminID);

        $eventLogs = $logModel->getWhere([
            "Operation" => "Automation",
            "RecordType" => "Discussion",
            "RecordID" => $discussion["discussionID"],
            "AutomationRuleRevisionID" => $automationRule["automationRuleRevisionID"],
        ]);
        $this->assertCount(1, $eventLogs);
    }

    /**
     * Create an automation rule
     *
     * @param array $record
     * @return array
     */
    private function createAutomationRule(array $record): array
    {
        $response = $this->api()->post(self::AUTOMATION_URI, $record);
        $this->assertEquals(201, $response->getStatusCode());
        return $response->getBody();
    }
}
