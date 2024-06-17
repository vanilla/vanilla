<?php
/**
 * @author Sooraj Francis <sfrancis@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace AutomationRules\Triggers;

use VanillaTests\SiteTestCase;

/**
 * Test time based trigger calculations
 */
class TimedTriggerTest extends SiteTestCase
{
    /**
     * Test time based date ranges
     *
     * @param array $data
     * @return void
     * @dataProvider dateRangeDataProvider
     */
    public function testGetDateRange(array $data): void
    {
        //Get the current date time
        $currentDate = new \DateTimeImmutable();
        $trigger = new TestTrigger();
        // Get the date, the rule ran last time if exists
        $lastRunDate = isset($data["timeInterval"]) ? $currentDate->sub($data["timeInterval"]) : null;
        $dateRange = $trigger->getDateRange($data["triggerValue"], $lastRunDate);
        $expected = $this->getExpectedResult($data["type"]);
        // Validate the date range
        $this->assertEquals($expected["min"], $dateRange["min"]->format("Y-m-d H:i"));
        $this->assertEquals($expected["max"], $dateRange["max"]->format("Y-m-d H:i"));
        // In case of no max time threshold, the difference between min and max should be exactly 1 minute
        if ($data["type"] === "noMax") {
            $this->assertEquals(60, $dateRange["max"]->getTimestamp() - $dateRange["min"]->getTimestamp()); //exactly a minute difference
        }
    }

    /**
     * Provide expected results for 'testGetDateRange()'
     *
     * @param string $type
     * @return array
     */
    private function getExpectedResult(string $type): array
    {
        $currentDate = new \DateTimeImmutable();
        $currentTime = $currentDate->getTimestamp();
        return match ($type) {
            "noMax" => [
                "min" => date("Y-m-d H:i", $currentTime - 3660),
                "max" => date("Y-m-d H:i", $currentTime - 3600),
            ],
            "withMax", "withTimeSinceLastRun" => [
                "min" => date("Y-m-d H:i", $currentTime - 7200),
                "max" => date("Y-m-d H:i", $currentTime - 3600),
            ],
            "withTimeSinceLastRunNoMax" => [
                "min" => $currentDate->sub(new \DateInterval("PT25H"))->format("Y-m-d H:i"),
                "max" => date("Y-m-d H:i", $currentTime - 3600),
            ],
            "withTimeSinceLastRunNoMaxThreshold" => [
                "min" => $currentDate->sub(new \DateInterval("PT25H"))->format("Y-m-d H:i"),
                "max" => $currentDate->sub(new \DateInterval("PT24H"))->format("Y-m-d H:i"),
            ],
        };
    }

    /**
     * Data provider for testGetDateRange
     *
     * @return array
     */
    public function dateRangeDataProvider(): array
    {
        /*
         * provide different dataset based on time based trigger configuration to validate the date range.
         */
        return [
            //no max time threshold set
            "No Max Time Threshold" => [
                "data" => [
                    "type" => "noMax",
                    "triggerValue" => [
                        "triggerTimeThreshold" => 1,
                        "triggerTimeUnit" => "hour",
                    ],
                ],
            ],
            //provide max time threshold value
            "With Max Time Threshold" => [
                "data" => [
                    "type" => "withMax",
                    "triggerValue" => [
                        "triggerTimeThreshold" => 1,
                        "triggerTimeUnit" => "hour",
                        "maxTimeThreshold" => 2,
                        "maxTimeUnit" => "hour",
                    ],
                ],
            ],
            //provide the last execution time for the rule
            "With Time Since Last Run" => [
                "data" => [
                    "type" => "withTimeSinceLastRun",
                    "triggerValue" => [
                        "triggerTimeThreshold" => 1,
                        "triggerTimeUnit" => "hour",
                        "maxTimeThreshold" => 2,
                        "maxTimeUnit" => "hour",
                    ],
                    "timeInterval" => new \DateInterval("PT1H"), //1 hour ago
                ],
            ],
            "With Time Since Last Run with a day difference" => [
                "data" => [
                    "type" => "withTimeSinceLastRunNoMax",
                    "triggerValue" => [
                        "triggerTimeThreshold" => 1,
                        "triggerTimeUnit" => "hour",
                    ],
                    "timeInterval" => new \DateInterval("P1D"), // last run was a day ago
                ],
            ],
            "With Time Since Last Run and trigger days" => [
                "data" => [
                    "type" => "withTimeSinceLastRunNoMaxThreshold",
                    "triggerValue" => [
                        "triggerTimeThreshold" => 1,
                        "triggerTimeUnit" => "day",
                    ],
                    "timeInterval" => new \DateInterval("PT1H"), // last run was an hour ago
                ],
            ],
        ];
    }
}
