<?php
/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace AutomationRules\Triggers;

use Garden\Schema\Schema;
use Vanilla\AutomationRules\Trigger\TimedAutomationTrigger;

class TestTrigger extends TimedAutomationTrigger
{
    /**
     * @inheritdoc
     */
    public static function getType(): string
    {
        return "testTrigger";
    }

    /**
     * @inheritdoc
     */
    public static function getName(): string
    {
        return "Test Trigger";
    }

    /**
     * @inheritdoc
     */
    public static function getActions(): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public static function getTriggerValueSchema(): Schema
    {
        return new Schema([]);
    }

    /**
     * @inheritdoc
     */
    public static function getSchema(): Schema
    {
        return new Schema([]);
    }

    /**
     * Get Automation rule time range
     *
     * @param array $triggerValue
     * @param \DateTimeImmutable|null $lastRunDate
     * @return array
     */
    public function getDateRange(array $triggerValue, ?\DateTimeImmutable $lastRunDate = null): array
    {
        $range = $this->getTimeBasedDateRange($triggerValue, $lastRunDate);
        $min = $range->getValue(">=");
        $max = $range->getValue("<=");
        return [
            "min" => $min,
            "max" => $max,
        ];
    }

    /**
     * @inheritdoc
     */
    public function getRecordCountsToProcess(array $where): int
    {
        return 0;
    }

    /**
     * @inheritdoc
     */
    public static function getContentType(): string
    {
        return "test";
    }
}
