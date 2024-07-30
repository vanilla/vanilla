<?php
/**
 * @author Sooraj Francis <sfrancis@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Dashboard\AutomationRules\Schema;

use Vanilla\Dashboard\Models\AutomationRuleModel;
use Garden\Schema\Schema;
use Vanilla\ApiUtils;
use Vanilla\Schema\RangeExpression;

/**
 * Input schema for the /api/v2/automation-rules/recipes endpoint
 */
class AutomationRuleInputSchema extends Schema
{
    /**
     * @inheritDoc
     */
    public function __construct()
    {
        parent::__construct(
            $this->parseInternal([
                "automationRuleID?" => RangeExpression::createSchema([":int"])->setDescription(
                    "Filter to specific automation rules."
                ),
                "name:s?" => [
                    "description" => "Filter to specific recipe names.",
                    "minLength" => 1,
                    "maxLength" => 100,
                    "style" => "form",
                ],
                "status:a?" => [
                    "description" => "Filter to specific automation rule status.",
                    "default" => [AutomationRuleModel::STATUS_ACTIVE, AutomationRuleModel::STATUS_INACTIVE],
                    "items" => [
                        "type" => "string",
                        "enum" => AutomationRuleModel::STATUS_OPTIONS,
                    ],
                    "style" => "form",
                ],
                "sort:a?" => [
                    "description" => "Sort the results.",
                    "default" => ["status", "-dateLastRun"],
                    "items" => [
                        "type" => "string",
                        "enum" => ApiUtils::sortEnum("status", "dateInserted", "dateUpdated", "dateLastRun"),
                    ],
                    "style" => "form",
                ],
                "limit:i?" => [
                    "description" => "Limit the number of results.",
                    "default" => AutomationRuleModel::MAX_LIMIT,
                    "minimum" => 1,
                    "maximum" => AutomationRuleModel::MAX_LIMIT,
                ],
                "escalations:b?" => [
                    "default" => false,
                    "description" => "Filter by rules with escalation actions.",
                ],
                "expand?" => ApiUtils::getExpandDefinition(
                    ["insertUserID", "updateUserID", "dispatchStatus", "all"],
                    null
                ),
            ])
        );
        $this->setDescription("Input parameters for the /api/v2/automation-rules/recipes endpoint.");
    }
}
