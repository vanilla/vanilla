<?php
/**
 * @author Sooraj Francis <sfrancis@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace AutomationRules\Actions;

use Vanilla\AutomationRules\Triggers\StaleDiscussionTrigger;
use Vanilla\CurrentTimeStamp;
use Vanilla\Dashboard\Models\AutomationRuleDispatchesModel;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\SiteTestCase;
use LogModel;

class MoveDiscussionToCategoryActionTest extends SiteTestCase
{
    use CommunityApiTestTrait;

    private AutomationRuleDispatchesModel $automationRuleDispatchesModel;

    private LogModel $logModel;

    /**
     * @inheritDoc
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->automationRuleDispatchesModel = $this->container()->get(AutomationRuleDispatchesModel::class);
        $this->logModel = $this->container()->get(LogModel::class);
    }

    /**
     * Test Move Discussion to category action
     *
     * @return void
     * @throws \Garden\Container\ContainerException
     * @throws \Garden\Container\NotFoundException
     * @throws \Garden\Schema\ValidationException
     */
    public function testMoveDiscussionToCategoryAction()
    {
        CurrentTimeStamp::mockTime(strtotime("-10 days"));

        // Create 2 categories
        $category1 = $this->createCategory();
        $category2 = $this->createCategory();

        // Create two discussions
        $discussion1 = $this->createDiscussion([
            "categoryID" => $category1["categoryID"],
        ]);

        $discussion2 = $this->createDiscussion([
            "categoryID" => $category2["categoryID"],
        ]);

        CurrentTimeStamp::clearMockTime();

        // Create the automation rule
        $automationRecord = [
            "name" => "Move Discussion to Category Automation Rule",
            "trigger" => [
                "triggerType" => StaleDiscussionTrigger::getType(),
                "triggerValue" => [
                    "applyToNewContentOnly" => false,
                    "triggerTimeLookBackLimit" => [
                        "length" => 12,
                        "unit" => "day",
                    ],
                    "triggerTimeDelay" => [
                        "length" => 6,
                        "unit" => "day",
                    ],
                    "postType" => ["discussion"],
                ],
            ],
            "action" => [
                "actionType" => "moveToCategoryAction",
                "actionValue" => [
                    "categoryID" => $category2["categoryID"],
                ],
            ],
        ];

        $result = $this->api()->post("automation-rules", $automationRecord);
        $this->assertEquals(201, $result->getStatusCode());
        $automationRule = $result->getBody();

        // Run the automation rule manually
        $this->api()->post("automation-rules/{$automationRule["automationRuleID"]}/trigger");

        $dispatches = $this->automationRuleDispatchesModel->getRecentDispatchByAutomationRuleRevisionIDs([
            $automationRule["automationRuleRevisionID"],
        ]);

        $this->assertCount(1, $dispatches);
        $dispatches = array_shift($dispatches);
        $dispatches["attributes"] = json_decode($dispatches["attributes"], true);
        $this->assertEquals(1, $dispatches["attributes"]["estimatedRecordCount"]);
        $this->assertEquals(1, $dispatches["attributes"]["affectedRecordCount"]);

        // Get the logs
        $logs = $this->logModel->getWhere([
            "DispatchUUID" => $dispatches["automationRuleDispatchUUID"],
            "AutomationRuleRevisionID" => $dispatches["automationRuleRevisionID"],
        ]);
        $this->assertCount(1, $logs);
        $this->assertEquals(
            [
                "recordID" => $discussion1["discussionID"],
                "fromCategoryID" => $category1["categoryID"],
                "toCategoryID" => $category2["categoryID"],
            ],
            $logs[0]["Data"]["moveDiscussion"]
        );
        // Get the updated discussion and verify that the category for the discussion has been updated
        $updatedDiscussion = $this->api()
            ->get("discussions/{$discussion1["discussionID"]}")
            ->getBody();
        $this->assertEquals($category2["categoryID"], $updatedDiscussion["categoryID"]);
    }
}
