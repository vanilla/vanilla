<?php
/**
 * @author Sooraj Francis <sfrancis@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\AutomationRules\Trigger;

use DateTimeImmutable;

/**
 * Interface for timed automation trigger
 */
interface TimedAutomationTriggerInterface
{
    /**
     * provide `where` condition based on trigger values and date offset
     *
     * @param array $triggerValue
     * @param DateTimeImmutable|null $lastRunDate
     * @return array
     */
    public function getWhereArray(array $triggerValue, ?DateTimeImmutable $lastRunDate = null): array;
}
