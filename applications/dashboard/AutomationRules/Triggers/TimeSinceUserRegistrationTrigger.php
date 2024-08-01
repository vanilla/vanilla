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
 * Class StaleDiscussionTrigger
 */
class TimeSinceUserRegistrationTrigger extends TimedAutomationTrigger
{
    /**
     * @inheridoc
     */
    public static function getType(): string
    {
        return "timeSinceUserRegistrationTrigger";
    }

    /**
     * @inheridoc
     */
    public static function getName(): string
    {
        return "Time since Registration";
    }

    /**
     * @inheridoc
     */
    public static function getContentType(): string
    {
        return "users";
    }

    /**
     * @inheridoc
     */
    public static function getActions(): array
    {
        return UserRuleDataType::getActions();
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
        return "UserID";
    }

    /**
     * @inheridoc
     */
    private function getObjectModel(): \Gdn_Model
    {
        return Gdn::getContainer()->get(UserModel::class);
    }

    /**
     * @inheridoc
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
     * @inheridoc
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
     * @inheridoc
     */
    public function getRecordCountsToProcess(array $where): int
    {
        return $this->getObjectModel()->getCount($where);
    }

    /**
     * @inheridoc
     */
    static function getSchema(): Schema
    {
        $schema = self::getTimeIntervalSchema();
        $schema["additionalSettings"] = self::getAdditionalSettingsSchema();
        return Schema::parse($schema);
    }

    /**
     * @inheridoc
     */
    public static function getTriggerValueSchema(): Schema
    {
        $triggerSchema = Schema::parse(self::getTimeIntervalParseSchema());
        self::addTimedValidations($triggerSchema);
        return $triggerSchema;
    }
}
