<?php

/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Dashboard\AutomationRules\Triggers;

use DateTimeImmutable;
use Garden\Schema\Schema;
use Gdn;
use UserModel;
use Vanilla\AutomationRules\Models\AutomationRuleLongRunnerGenerator;
use Vanilla\AutomationRules\Trigger\TimedAutomationTrigger;
use Vanilla\Dashboard\AutomationRules\Models\UserRuleDataType;

/**
 * Class TimeSinceUserRegistrationTrigger
 */
class TimeSinceUserRegistrationTrigger extends TimedAutomationTrigger
{
    /**
     * @inheritdoc
     */
    public static function getType(): string
    {
        return "timeSinceUserRegistrationTrigger";
    }

    /**
     * @inheritdoc
     */
    public static function getName(): string
    {
        return "Time since Registration";
    }

    /**
     * @inheritdoc
     */
    public static function getContentType(): string
    {
        return "users";
    }

    /**
     * @inheritdoc
     */
    public static function getActions(): array
    {
        return UserRuleDataType::getActions();
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
        return "UserID";
    }

    /**
     * @inheritdoc
     */
    private function getObjectModel(): \Gdn_Model
    {
        return Gdn::getContainer()->get(UserModel::class);
    }

    /**
     * @inheritdoc
     */
    public function getWhereArray(array $triggerValue, ?DateTimeImmutable $lastRunDate = null): array
    {
        $dateRange = $this->getTimeBasedDateRange($triggerValue, $lastRunDate);
        return [
            "Banned" => 0,
            "Deleted" => 0,
            "DateInserted" => $dateRange,
        ];
    }

    /**
     * @inheritdoc
     */
    public function getRecordsToProcess($lastRecordId, array $where): iterable
    {
        if (!empty($lastRecordId)) {
            $where[$this->getPrimaryKey() . ">"] = (int) $lastRecordId;
        }
        return $this->getObjectModel()->getWhereIterator(
            $where,
            "UserID",
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
        return $this->getObjectModel()->getCount($where);
    }

    /**
     * @inheritdoc
     */
    static function getSchema(): Schema
    {
        $schema = self::getTimeIntervalSchema();
        $schema["additionalSettings"] = self::getAdditionalSettingsSchema();
        return Schema::parse($schema);
    }

    /**
     * @inheritdoc
     */
    public static function getTriggerValueSchema(): Schema
    {
        $triggerSchema = Schema::parse(self::getTimeIntervalParseSchema());
        self::addTimedValidations($triggerSchema);
        return $triggerSchema;
    }
}
