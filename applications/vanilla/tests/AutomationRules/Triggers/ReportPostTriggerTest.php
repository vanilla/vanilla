<?php
/**
 * @author Pavel Goncharov <pgoncharov@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace AutomationRules\Triggers;

use CategoryModel;
use LogModel;
use Vanilla\AutomationRules\Actions\CreateEscalationAction;
use Vanilla\AutomationRules\Triggers\ReportPostTrigger;
use Vanilla\CurrentTimeStamp;
use Vanilla\Dashboard\Models\AutomationRuleDispatchesModel;
use VanillaTests\AutomationRules\AutomationRulesTestTrait;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\Models\TestCommentModelTrait;
use VanillaTests\Models\TestDiscussionModelTrait;
use VanillaTests\SiteTestCase;
use VanillaTests\UsersAndRolesApiTestTrait;

class ReportPostTriggerTest extends SiteTestCase
{
    use AutomationRulesTestTrait,
        CommunityApiTestTrait,
        UsersAndRolesApiTestTrait,
        TestDiscussionModelTrait,
        TestCommentModelTrait;

    private CategoryModel $categoryModel;
    private LogModel $logModel;

    /**
     * @inheritdoc
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->initialize();
        \Gdn::config()->set("Feature.CommunityManagementBeta.Enabled", true);
        \Gdn::config()->set("Feature.escalations.Enabled", true);
        $this->logModel = $this->container()->get(\LogModel::class);
    }

    /**
     * Get a test automation record
     *
     * @param string $actionType
     * @param int $countReports
     * @param string $reportReasonID
     * @param array $triggerValue
     * @return array[]
     */
    private function getAutomationRecord(
        string $actionType,
        int $countReports,
        string $reportReasonID,
        array $triggerValue
    ): array {
        return [
            "trigger" => [
                "type" => ReportPostTrigger::getType(),
                "value" => ["countReports" => $countReports, "reportReasonID" => [$reportReasonID]],
            ],
            "action" => [
                "type" => $actionType,
                "value" => $triggerValue,
            ],
        ];
    }

    /**
     * Test that post is processed when it is reported.
     *
     */
    public function testAutomationRuleIsProcessedReportedPosts(): void
    {
        $this->createCategory();
        $discussion = $this->createDiscussion();

        $this->createDiscussion();
        $comment = $this->createComment();
        $automationRecord = $this->getAutomationRecord(CreateEscalationAction::getType(), 1, "spam", [
            "recordIsLive" => true,
        ]);

        $automationRule = $this->createAutomationRule($automationRecord["trigger"], $automationRecord["action"]);

        $this->createReport($discussion, [
            "reportReasonIDs" => ["spam", "abuse"],
            "noteBody" => "*Bold*",
        ]);
        CurrentTimeStamp::mockTime("+10 minutes");

        $this->createReport($comment, [
            "reportReasonIDs" => ["spam", "abuse"],
            "noteBody" => "*Bold*",
        ]);

        //Should create exactly two dispatch and a log
        $dispatches = $this->getDispatchedRules($automationRule["automationRuleID"], ["success"]);
        $this->assertCount(2, $dispatches);
        $this->assertRowsLike(
            [
                "affectedRecordType" => ["discussion", "comment"],
                "estimatedRecordCount" => [1, 1],
                "affectedRecordCount" => [1, 1],
            ],
            array_column($dispatches, "attributes")
        );

        $escalations = $this->api()
            ->get("/escalations", ["placeRecordType" => "category", "placeRecordID" => $this->lastInsertedCategoryID])
            ->getBody();
        $this->assertCount(2, $escalations);
        $this->assertRowsLike(
            [
                "recordType" => ["comment", "discussion"],
                "recordID" => [$discussion["discussionID"], $comment["commentID"]],
            ],
            $escalations
        );
    }

    /**
     * Tests that the automation trigger works when processed manually (i.e. when enabling for the first time).
     *
     * @return void
     */
    public function testAutomationRuleIsProcessedByManualExecution()
    {
        $reportReason = $this->createReportReason();

        $this->createCategory();
        $discussion = $this->createDiscussion();

        $this->createReport($discussion, [
            "reportReasonIDs" => [$reportReason["reportReasonID"]],
            "noteBody" => "test",
        ]);

        $automationRecord = $this->getAutomationRecord(
            CreateEscalationAction::getType(),
            1,
            $reportReason["reportReasonID"],
            [
                "recordIsLive" => true,
            ]
        );

        $automationRule = $this->createAutomationRule($automationRecord["trigger"], $automationRecord["action"]);

        $this->api()->post("automation-rules/{$automationRule["automationRuleID"]}/trigger");

        //Should create exactly two dispatch and a log
        $dispatches = $this->getDispatchedRules(
            $automationRule["automationRuleID"],
            ["success"],
            AutomationRuleDispatchesModel::TYPE_MANUAL
        );
        $this->assertCount(1, $dispatches);

        $this->assertRowsLike(
            [
                "affectedRecordType" => ["discussion"],
                "estimatedRecordCount" => [1],
                "affectedRecordCount" => [1],
            ],
            array_column($dispatches, "attributes")
        );

        $escalations = $this->api()
            ->get("/escalations", ["placeRecordType" => "category", "placeRecordID" => $this->lastInsertedCategoryID])
            ->getBody();
        $this->assertCount(1, $escalations);
        $this->assertRowsLike(
            [
                "recordType" => ["discussion"],
                "recordID" => [$discussion["discussionID"]],
            ],
            $escalations
        );
    }
}
