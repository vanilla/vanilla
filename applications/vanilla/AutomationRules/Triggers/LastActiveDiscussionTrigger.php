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
     * @inheritdoc
     */
    public static function getType(): string
    {
        return "lastActiveDiscussionTrigger";
    }

    /**
     * @inheritdoc
     */
    public static function getName(): string
    {
        return "Time since post has had no activity";
    }

    /**
     * @inheritdoc
     */
    public static function getContentType(): string
    {
        return "posts";
    }

    /**
     * @inheritdoc
     */
    public static function getActions(): array
    {
        return DiscussionRuleDataType::getActions();
    }

    /**
     * @inheritdoc
     */
    public static function getPostPatchSchema(Schema &$schema): void
    {
        $discussionCommentSchema = Schema::parse([
            "trigger:o" => self::getTimedTriggerSchema(),
        ]);
        $schema->merge($discussionCommentSchema);
    }

    /**
     * @inheritdoc
     */
    private function getPrimaryKey(): string
    {
        return "DiscussionID";
    }

    /**
     * @inheritdoc
     */
    private function getObjectModel(): \Gdn_Model
    {
        return Gdn::getContainer()->get(DiscussionModel::class);
    }

    /**
     * @inheritdoc
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
     * @inheritdoc
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
     * @inheritdoc
     */
    public function getRecordCountsToProcess(array $where): int
    {
        $sql = $this->getObjectModel()->SQL;
        // We need to ensure that NULL are treated as discussions.
        if (!empty($where["Type"])) {
            PostTypeModel::whereParentPostType($sql, $where["Type"]);
            unset($where["Type"]);
        }
        return $sql->getCount("Discussion", $where);
    }

    /**
     * @inheritdoc
     */
    static function getSchema(): Schema
    {
        return parent::getDiscussionSchema();
    }

    /**
     * @inheritdoc
     */
    public static function getTriggerValueSchema(): Schema
    {
        return parent::getDiscussionTriggerValueSchema();
    }
}
