<?php
/**
 * @author Pavel Goncharov <pgoncharov@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\AutomationRules\Models;

use Vanilla\Dashboard\AutomationRules\AutomationRuleService;
use Gdn_Database;
use Gdn_Session;
use Psr\Log\LoggerInterface;
use UserModel;
use Vanilla\Dashboard\Models\AutomationRuleDispatchesModel;
use Vanilla\Dashboard\Models\AutomationRuleModel;
use Vanilla\Dashboard\Models\AutomationRuleRevisionModel;

/**
 * AutomationRuleModel
 */
class MockAutomationRuleModel extends AutomationRuleModel
{
    /**
     * AutomationRuleModel constructor.
     *
     * @param Gdn_Session $session
     * @param UserModel $userModel
     * @param AutomationRuleRevisionModel $automationRuleRevisionModel
     * @param AutomationRuleDispatchesModel $automationRuleDispatchesModel ,
     * @param LoggerInterface $logger
     * @param Gdn_Database $database
     */
    public function __construct(
        Gdn_Session $session,
        UserModel $userModel,
        AutomationRuleRevisionModel $automationRuleRevisionModel,
        AutomationRuleDispatchesModel $automationRuleDispatchesModel,
        LoggerInterface $logger,
        Gdn_Database $database
    ) {
        parent::__construct(
            $session,
            $userModel,
            $automationRuleRevisionModel,
            $automationRuleDispatchesModel,
            $logger
        );
        $this->database = $database;
    }

    /**
     * Runs automation rule by Rule ID
     *
     * @param int $automationRuleID
     * @param string $dispatchType
     * @return void
     */
    public function startAutomationRunByID(
        int $automationRuleID,
        string $dispatchType = AutomationRuleDispatchesModel::TYPE_INITIAL
    ): void {
        return;
    }
}
