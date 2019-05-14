<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Web\Asset;

use PHPUnit\Framework\TestCase;
use Vanilla\Web\Asset\DeploymentCacheBuster;
use VanillaTests\Fixtures\MockConfig;

/**
 * Tests for the deployment cache buster.
 */
class DeploymentCacheBusterTest extends TestCase {
    /**
     * Test that it falls back if the config value is not set.
     */
    public function testFallback() {
        $buster = new DeploymentCacheBuster(
            new \DateTimeImmutable(1000),
            null
        );

        $this->assertEquals(APPLICATION_VERSION, $buster->value());
    }

    /**
     * Test that the generated value is accurate.
     */
    public function testValue() {
        $firstValue = (new DeploymentCacheBuster(
            new \DateTimeImmutable(1000),
            900
        ))->value();
        $secondValue = (new DeploymentCacheBuster(
            new \DateTimeImmutable(1000),
            900
        ))->value();
        $this->assertEquals($firstValue, $secondValue, "Busters should be consistent between each other");

        $thirdValue = (new DeploymentCacheBuster(
            new \DateTimeImmutable(1000),
            500
        ))->value();
        $this->assertNotEquals(
            $secondValue,
            $thirdValue,
            "Busters created at different times should have different values"
        );

        $deployTime = 900;
        $beforeGracePeriodEllapsed = (new DeploymentCacheBuster(
            new \DateTimeImmutable('@' . ($deployTime + DeploymentCacheBuster::GRACE_PERIOD - 1)),
            $deployTime
        ))->value();
        $afterGracePeriodEllapsed = (new DeploymentCacheBuster(
            new \DateTimeImmutable('@' . ($deployTime + DeploymentCacheBuster::GRACE_PERIOD + 1)),
            $deployTime
        ))->value();

        $this->assertNotEquals(
            $beforeGracePeriodEllapsed,
            $afterGracePeriodEllapsed,
            "The buster should change after the grace period."
        );
    }

    /**
     * Test that subsequent calls for the buster's value should be equal.
     */
    public function testConsistentValue() {
        $buster = new DeploymentCacheBuster(
            new \DateTimeImmutable(1000),
            500
        );

        $valueA = $buster->value();
        $valueB = $buster->value();

        $this->assertEquals($valueA, $valueB);
    }
}
