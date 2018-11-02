<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Web\Asset;

use PHPUnit\Framework\TestCase;
use Vanilla\Web\Asset\DeploymentCacheBuster;
use VanillaTests\Fixtures\MockConfig;

class DeploymentCacheBusterTest extends TestCase {
    /**
     * Test that it falls back if the config value is not set.
     */
    public function testFallback() {
        $buster = new DeploymentCacheBuster(
            new \DateTimeImmutable(1000),
            new MockConfig()
        );

        $this->assertEquals($buster->value(), APPLICATION_VERSION);
    }

    public function testValue() {

        $buster = new DeploymentCacheBuster(
            new \DateTimeImmutable(1000),
            new MockConfig()
        );

        $this->assertEquals($buster->value(), APPLICATION_VERSION);
    }
}
