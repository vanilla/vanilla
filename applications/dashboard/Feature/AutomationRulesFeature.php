<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Feature;

use Vanilla\Dashboard\Models\AutomationRuleModel;
use Vanilla\Feature\Feature;

/**
 * Feature class for Automation Rules.
 */
class AutomationRulesFeature extends Feature
{
    /**
     * Constructor.
     */
    public function __construct(private AutomationRuleModel $automationRuleModel)
    {
        parent::__construct("Automation Rules");
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        $countRules = $this->automationRuleModel->selectPagingCount(
            where: ["systemInitialRuleID IS NULL" => null, "status" => "active"],
            limit: 1
        );

        return $countRules > 0;
    }
}
