<?php
/**
 * @author Sooraj Francis <sfrancis@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Dashboard\EmailTemplate\Schema;

use Vanilla\Dashboard\Models\AutomationRuleModel;
use Garden\Schema\Schema;
use Vanilla\ApiUtils;
use Vanilla\Dashboard\Models\EmailTemplateModel;
use Vanilla\Schema\RangeExpression;

/**
 * Input schema for the /api/v2/email-template endpoint
 */
class EmailTemplateInputSchema extends Schema
{
    /**
     * @inheritdoc
     */
    public function __construct()
    {
        parent::__construct(
            $this->parseInternal([
                "emailTemplateID?" => RangeExpression::createSchema([":int"])->setDescription(
                    "Filter to specific email template."
                ),
                "name:s?" => [
                    "description" => "Filter to specific template names.",
                    "minLength" => 1,
                    "maxLength" => 100,
                    "style" => "form",
                ],
                "status:a?" => [
                    "description" => "Filter to specific email template status.",
                    "default" => [EmailTemplateModel::STATUS_ACTIVE, EmailTemplateModel::STATUS_INACTIVE],
                    "items" => [
                        "type" => "string",
                        "enum" => EmailTemplateModel::STATUS_OPTIONS,
                    ],
                    "style" => "form",
                ],
                "sort:a?" => [
                    "description" => "Sort the results.",
                    "default" => ["status"],
                    "items" => [
                        "type" => "string",
                        "enum" => ApiUtils::sortEnum("status", "dateInserted", "dateUpdated", "nane"),
                    ],
                    "style" => "form",
                ],
                "limit:i?" => [
                    "description" => "Limit the number of results.",
                    "default" => EmailTemplateModel::MAX_LIMIT,
                    "minimum" => 1,
                    "maximum" => EmailTemplateModel::MAX_LIMIT,
                ],
                "expand?" => ApiUtils::getExpandDefinition(
                    ["insertUserID", "updateUserID", "dispatchStatus", "all"],
                    null
                ),
            ])
        );
        $this->setDescription("Input parameters for the /api/v2/email-template endpoint.");
    }
}
