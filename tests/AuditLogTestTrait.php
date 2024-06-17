<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests;

use PHPUnit\Framework\TestCase;
use Vanilla\Logging\AuditLogModel;

/**
 * Test trait for asserting audit logs.
 */
trait AuditLogTestTrait
{
    /**
     * @return void
     */
    public static function setUpBeforeClassAuditLogTestTrait(): void
    {
        \Gdn::config()->saveToConfig("auditLog.enabled", true);
    }

    /**
     * @return void
     */
    public function setUpAuditLogTestTrait(): void
    {
        \Gdn::config()->logAudits();
        $this->resetTable("auditLog");
    }

    /**
     * Assert that an audit was not logged.
     *
     * @param ExpectedAuditLog $expectedAuditLog
     * @return void
     */
    public function assertNotAuditLogged(ExpectedAuditLog $expectedAuditLog): void
    {
        $this->assertAuditLogged($expectedAuditLog, 0);
    }

    /**
     * Assert that an audit was logged.
     *
     * @param ExpectedAuditLog $expectedAuditLog
     * @param int $expectedCount
     *
     * @return void
     */
    public function assertAuditLogged(ExpectedAuditLog $expectedAuditLog, int $expectedCount = 1): void
    {
        $model = \Gdn::getContainer()->get(AuditLogModel::class);
        $allAuditLogs = $model->select([
            "eventType" => $expectedAuditLog->expectedEventType,
        ]);
        $allAuditLogs = array_column($allAuditLogs, null, "auditLogID");

        if ($expectedCount > 0 && count($allAuditLogs) === 0) {
            $foundEventTypes = $model->selectEventTypes();
            TestCase::assertContains(
                $expectedAuditLog->expectedEventType,
                $foundEventTypes,
                "No audit logs found for event type '{$expectedAuditLog->expectedEventType}'"
            );
        }

        $model->normalizeRows($allAuditLogs);

        $matchingAudits = [];
        foreach ($allAuditLogs as $auditLog) {
            if ($expectedAuditLog->matches($auditLog)) {
                $matchingAudits[$auditLog["auditLogID"]] = $expectedAuditLog->formatActualEventRow($auditLog);
            }
        }

        $expectedLog = $expectedAuditLog->getExpectedRow();

        if ($expectedCount > 0) {
            TestCase::assertNotEmpty(
                array_values($matchingAudits),
                "No audit logs found matching the expected audit log\nExpected:\n" .
                    json_encode($expectedLog, JSON_PRETTY_PRINT) .
                    "\n" .
                    "Not Matching:\n" .
                    json_encode(array_values($allAuditLogs), JSON_PRETTY_PRINT)
            );
        } else {
            $fullMatchingAudits = [];
            foreach ($matchingAudits as $auditID => $matchingAudit) {
                $fullMatchingAudits[] = $allAuditLogs[$auditID];
            }
            TestCase::assertEmpty(
                array_values($fullMatchingAudits),
                "Found audit logs matching the expected audit log, but none were expected" .
                    json_encode($expectedLog, JSON_PRETTY_PRINT) .
                    "\n" .
                    "Matching:\n" .
                    json_encode($fullMatchingAudits, JSON_PRETTY_PRINT)
            );
        }

        TestCase::assertCount(
            $expectedCount,
            array_values($matchingAudits),
            "Expected to find $expectedCount matching audit logs. Either by more precise with your expected event, clear events with resetAuditLogs() or increase your expected count."
        );
    }
}
