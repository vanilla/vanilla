<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

use VanillaTests\SiteTestCase;

/**
 * Test posting a tick.
 */
class TickTest extends SiteTestCase
{
    /**
     * Test that no error is thrown when Gdn::controller() is null and we post a tick.
     */
    public function testTick()
    {
        $this->enableCaching();
        // We need certain config values in order to hit the place where Gdn::controller() is called.
        $this->runWithConfig(
            [
                "Garden.Analytics.Views.Denormalize" => true,
                "Garden.Analytics.Views.DenormalizeWriteback" => 1,
            ],
            function () {
                $this->expectNotToPerformAssertions();
                $this->api()->post("tick");
            }
        );
    }
}
