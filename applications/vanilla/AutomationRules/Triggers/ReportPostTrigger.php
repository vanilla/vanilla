<?php

/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\AutomationRules\Triggers;

use Garden\Schema\Invalid;
use Garden\Schema\Schema;
use Garden\Schema\ValidationField;
use Gdn;
use Vanilla\AutomationRules\Models\AutomationRuleLongRunnerGenerator;
use Vanilla\AutomationRules\Trigger\AutomationTrigger;
use Vanilla\Dashboard\AutomationRules\Models\EscalationRuleDataType;
use Vanilla\FeatureFlagHelper;
use Vanilla\Forms\ApiFormChoices;
use Vanilla\Forms\FieldMatchConditional;
use Vanilla\Forms\FormOptions;
use Vanilla\Forms\SchemaForm;
use Vanilla\Forms\StaticFormChoices;
use Vanilla\Forum\Models\CommunityManagement\ReportModel;
use Vanilla\Forum\Models\CommunityManagement\ReportReasonModel;
use Vanilla\Models\Model;

/**
 * Class ReportPostTrigger
 */
class ReportPostTrigger extends AutomationTrigger
{
    private ReportModel $reportModel;

    /**
     * @inheridoc
     */
    public static function getType(): string
    {
        return "reportPostTrigger";
    }

    /**
     * @inheridoc
     */
    public static function getName(): string
    {
        return "Post received reports";
    }

    /**
     * @inheridoc
     */
    public static function getContentType(): string
    {
        return "posts";
    }

    /**
     * @inheridoc
     */
    public static function getActions(): array
    {
        return EscalationRuleDataType::getActions();
    }

    /**
     * @inheridoc
     */
    public static function canAddTrigger(): bool
    {
        return FeatureFlagHelper::featureEnabled("CommunityManagementBeta") &&
            FeatureFlagHelper::featureEnabled("escalations");
    }

    /**
     * @inheridoc
     */
    public static function getSchema(): Schema
    {
        $reportReasonModel = Gdn::getContainer()->get(ReportReasonModel::class);
        $formChoices = $reportReasonModel->selectReasons(["isSystem" => false]);

        $schema = [
            "countReports" => [
                "type" => "integer",
                "required" => true,
                "x-control" => SchemaForm::textBox(
                    new FormOptions("Number of Reports", "The number of reports received on a post"),
                    "integer"
                ),
            ],
            "reportReasonID?" => [
                "type" => "array",
                "items" => [
                    "type" => "string",
                ],
                "default" => [],
                "enum" => array_keys($formChoices),
                "x-control" => SchemaForm::dropDown(
                    new FormOptions("Report Reason"),
                    new StaticFormChoices($formChoices),
                    null,
                    true
                ),
            ],
            "categoryID?" => [
                "required" => false,
                "type" => "array",
                "items" => [
                    "type" => "integer",
                ],
                "x-control" => SchemaForm::dropDown(
                    new FormOptions("Category"),
                    new ApiFormChoices("/api/v2/categories", "/api/v2/categories/%s", "categoryID", "name"),
                    null,
                    true
                ),
            ],
            "includeSubcategories?" => [
                "type" => "boolean",
                "x-control" => SchemaForm::checkBox(
                    new FormOptions(
                        "Include Subcategories",
                        "Include content from subcategories of the chosen category"
                    ),
                    new FieldMatchConditional(
                        "trigger.triggerValue",
                        Schema::parse([
                            "categoryID" => [
                                "type" => "array",
                                "items" => ["type" => "integer"],
                                "minItems" => 1,
                            ],
                        ])
                    )
                ),
            ],
        ];

        return Schema::parse($schema);
    }

    /**
     * @inheridoc
     */
    public static function getPostPatchSchema(Schema &$schema): void
    {
        $reportPostValueSchema = Schema::parse([
            "countReports" => [
                "nullable" => true,
            ],
            "reportReasonID?" => [
                "type" => "array",
                "items" => ["type" => "string"],
                "nullable" => true,
            ],
            "categoryID?" => [
                "type" => "array",
                "items" => ["type" => "integer"],
                "nullable" => true,
            ],
            "includeSubcategories?" => [
                "type" => "boolean",
                "nullable" => true,
            ],
        ])
            ->addValidator("countReports", function ($countReports, ValidationField $field) {
                if (empty($countReports)) {
                    $field->addError("missingField");
                    return Invalid::value();
                } elseif (
                    !is_numeric($countReports) ||
                    floor($countReports) !== ceil($countReports) ||
                    $countReports < 0
                ) {
                    $field->addError("Count Report should be positive whole numbers only.");
                    return Invalid::value();
                }
                return true;
            })
            ->addValidator("reportReasonID", function ($reportReason, ValidationField $field) {
                $reportReasonModel = Gdn::getContainer()->get(ReportReasonModel::class);
                $validPostTypes = $reportReasonModel->selectReasonIDs(["isSystem" => false]);
                $failed = false;
                if (!is_array($reportReason) || empty($reportReason)) {
                    $failed = false;
                } else {
                    foreach ($reportReason as $type) {
                        if (!in_array($type, $validPostTypes)) {
                            $failed = true;
                        }
                    }
                }
                if ($failed) {
                    $field->addError("Invalid reason type, Valid reasons are: " . json_encode($validPostTypes));
                    return Invalid::value();
                }
                return !$failed;
            });

        $reportPostSchema = Schema::parse([
            "trigger:o" => [
                "triggerType:s" => [
                    "enum" => [self::getType()],
                ],
                "triggerValue:o" => $reportPostValueSchema,
            ],
        ]);

        $schema->merge($reportPostSchema);
    }

    /**
     * @inheridoc
     */
    private function getPrimaryKey(): string
    {
        return "RecordID";
    }

    /**
     * @inheridoc
     */
    private function getObjectModel(): ReportModel
    {
        return Gdn::getContainer()->get(ReportModel::class);
    }

    /**
     * @inheridoc
     */
    public function getRecordCountsToProcess(array $where): int
    {
        $reportModel = Gdn::getContainer()->get(ReportModel::class);
        $query = $reportModel->createCountedReportsQuery($where);
        return $query->get()->count();
    }

    /**
     * @inheridoc
     */
    public function getRecordsToProcess($lastRecordId, array $where): iterable
    {
        $reportModel = Gdn::getContainer()->get(ReportModel::class);
        if (!empty($lastRecordId)) {
            $lastRecordId = (int) $lastRecordId;
            $where[$this->getPrimaryKey() . ">"] = $lastRecordId;
        }

        $where = $reportModel->createCountedReportsWhere($where);

        return $this->getObjectModel()->getWhereIterator(
            $where,
            ["recordType", "reportID"],
            "asc",
            AutomationRuleLongRunnerGenerator::BUCKET_SIZE
        );
    }
}
