<?php

/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\AutomationRules\Triggers;

use DateTimeImmutable;
use DiscussionModel;
use Garden\Schema\Schema;
use Gdn;
use Vanilla\AutomationRules\Models\AutomationRuleLongRunnerGenerator;
use Vanilla\AutomationRules\Trigger\TimedAutomationTrigger;
use Vanilla\Dashboard\AutomationRules\Models\DiscussionRuleDataType;
use Vanilla\FeatureFlagHelper;
use Vanilla\Forum\Models\PostTypeModel;

/**
 * Class StaleDiscussionTrigger
 */
class LastActiveDiscussionTrigger extends TimedAutomationTrigger
{
    /**
     * @inheridoc
     */
    public static function getType(): string
    {
        return "lastActiveDiscussionTrigger";
    }

    /**
     * @inheridoc
     */
    public static function getName(): string
    {
        return "Time since post has had no activity";
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
        return DiscussionRuleDataType::getActions();
    }

    /**
     * @inheridoc
     */
    public static function getPostPatchSchema(Schema &$schema): void
    {
        $discussionCommentSchema = Schema::parse([
            "trigger:o" => self::getTimedTriggerSchema(),
        ]);
        $schema->merge($discussionCommentSchema);
    }

    /**
     * @inheridoc
     */
    private function getPrimaryKey(): string
    {
        return "DiscussionID";
    }

    /**
     * @inheridoc
     */
    private function getObjectModel(): \Gdn_Model
    {
        return Gdn::getContainer()->get(DiscussionModel::class);
    }

    /**
     * @inheridoc
     */
    public function getWhereArray(array $triggerValue, ?DateTimeImmutable $lastRunDate = null): array
    {
        $dateRange = $this->getTimeBasedDateRange($triggerValue, $lastRunDate);
        return [
            "Closed" => 0,
            "Type" => $triggerValue["postType"],
            "DateLastComment" => $dateRange,
        ];
    }

    /**
     * @inheridoc
     */
    public function getRecordsToProcess($lastRecordId, array $where): \Generator
    {
        if (!empty($lastRecordId)) {
            $where[$this->getPrimaryKey() . ">"] = (int) $lastRecordId;
        }
        return $this->getObjectModel()->getWhereIterator(
            $where,
            "DiscussionID",
            "asc",
            false,
            AutomationRuleLongRunnerGenerator::BUCKET_SIZE
        );
    }

    /**
     * @inheridoc
     */
    public function getRecordCountsToProcess(array $where): int
    {
        $sql = $this->getObjectModel()->SQL;
        // We need to ensure that NULL are treated as discussions.
        if (!empty($where["Type"]) && in_array("discussion", $where["Type"])) {
            if (PostTypeModel::isPostTypesFeatureEnabled()) {
                PostTypeModel::addJoin($sql, $where["Type"]);
            } else {
                $sql->beginWhereGroup()
                    ->where("Type", $where["Type"])
                    ->orWhere("Type is null")
                    ->endWhereGroup();
            }
            unset($where["Type"]);
        }
        return $sql->getCount("Discussion", $where);
    }

    /**
     * @inheridoc
     */
    static function getSchema(): Schema
    {
        return parent::getDiscussionSchema();
    }

    /**
     * @inheridoc
     */
    public static function getTriggerValueSchema(): Schema
    {
        return parent::getDiscussionTriggerValueSchema();
    }
}
