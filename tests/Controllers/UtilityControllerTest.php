<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Controllers;

use Garden\Container\Container;
use Vanilla\AddonManager;
use Vanilla\Models\AddonModel;
use VanillaTests\Fixtures\TestAddonManager;
use VanillaTests\SiteTestCase;

/**
 * Tests for the utility controller.
 */
class UtilityControllerTest extends SiteTestCase
{
    public static $addons = ["bad-structure"];

    /**
     * Test that failures in the UpdateModel are reported properly.
     *
     * @param array $config The config to run with.
     * @param array $requestBody The request body.
     *
     * @dataProvider provideUpdateFailureResponse
     */
    public function testUpdateFailureResponse(array $config, array $requestBody = [])
    {
        $this->runWithConfig($config, function () use ($requestBody) {
            $config = \Gdn::config();
            $config->remove("Garden.Scheduler.Token");
            $schedulerToken = $config->get("Garden.Scheduler.Token", null);
            $response = $this->bessy()->postJsonData("/utility/update", $requestBody);
            $this->assertEquals(500, $response->getStatus());

            $this->assertLog([
                "message" => "Structure failed for addon {addonKey}",
                "data.addonKey" => "bad-structure",
            ]);
            $newSchedulerToken = $config->get("Garden.Scheduler.Token", null);
            $this->assertNull($schedulerToken);
            $this->assertNotNull($newSchedulerToken);
        });
    }

    /**
     * @return array[]
     */
    public function provideUpdateFailureResponse(): array
    {
        return [
            "With update token, debug" => [
                [
                    "Feature.updateTokens.Enabled" => true,
                    "Garden.UpdateToken" => "secret",
                ],
                ["updateToken" => "secret"],
            ],
            "No update token, debug" => [["Feature.updateTokens.Enabled" => false]],
        ];
    }
}
