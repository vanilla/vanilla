<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests;

use PHPUnit\Framework\TestCase;

/**
 * Class to represent an expected notification in a test.
 */
class ExpectedNotification
{
    /** @var string */
    private $expectedType;

    /** @var string[] */
    private $expectedFragments;

    /** @var string|null */
    private $reason;

    /**
     * Constructor.
     *
     * @param string $expectedType
     * @param string[] $expectedFragments
     * @param string|null $reason
     */
    public function __construct(string $expectedType, array $expectedFragments, ?string $reason = null)
    {
        $this->expectedType = $expectedType;
        $this->expectedFragments = $expectedFragments;
        $this->reason = $reason;
    }

    /**
     * Assert that this matches an actual notification.
     *
     * @param array $actualNotification
     */
    public function assertMatches(array $actualNotification)
    {
        TestCase::assertEquals(
            $this->expectedType,
            $actualNotification["activityType"],
            "Notification had incorrect activity type: " . json_encode($actualNotification, JSON_PRETTY_PRINT)
        );

        $notificationBody = $actualNotification["body"];
        foreach ($this->expectedFragments as $bodyFragment) {
            TestCase::assertStringContainsString(
                $bodyFragment,
                $notificationBody,
                "Notification was missing a fragment."
            );
        }

        if ($this->reason !== null) {
            TestCase::assertEquals($this->reason, $actualNotification["reason"]);
        }
    }
}
