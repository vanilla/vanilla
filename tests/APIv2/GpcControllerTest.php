<?php
/**
 * @author Olivier Lamy-Canuel <olamy-canuel@higherlogic.com>
 * @copyright 2009-2024 Higher Logic Inc.
 * @license Proprietary
 */

namespace APIv2;

use Garden\Web\Exception\ClientException;
use VanillaTests\SiteTestCase;

/**
 * Test the gpc API controller.
 */
class GpcControllerTest extends SiteTestCase
{
    protected static $addons = ["dashboard"];

    /**
     * Test that the GPC endpoint returns the expected values when enabled.
     *
     * @return void
     */
    public function testGpcEnabled(): void
    {
        $this->runWithConfig(["gpc.enabled" => true, "gpc.lastUpdate" => "2022-01-01 00:00:00"], function () {
            $data = $this->api()->get("/gpc");
            $this->assertTrue($data["gpc"]);
            $this->assertEquals("2022-01-01 00:00:00", $data["lastUpdate"]);
            $this->assertEquals("application/json", $data->getHeader("Content-Type"));
        });
    }

    /**
     * Test that the GPC endpoint fails when there are no enabled.
     *
     * @return void
     */
    public function testGpcNoGpc(): void
    {
        $this->runWithConfig(["gpc.lastUpdate" => "2022-01-01 00:00:00"], function () {
            $this->expectException(ClientException::class);
            $this->expectExceptionMessage("gpc is required");
            $this->api()->get("/gpc");
        });
    }

    /**
     * Test that the GPC endpoint fails when there are no lateUpdated.
     *
     * @return void
     */
    public function testGpcNoDate(): void
    {
        $this->runWithConfig(["gpc.enabled" => true], function () {
            $this->expectException(ClientException::class);
            $this->expectExceptionMessage("lastUpdate is required");
            $this->api()->get("/gpc");
        });
    }
}
