<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

use VanillaTests\SiteTestCase;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Test posting a tick.
 */
class TickTest extends SiteTestCase
{
    use UsersAndRolesApiTestTrait;
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

    /**
     * Tests that valid IP addresses (both IPv4 and IPv6) are logged in the UserIP table on each request to tick.
     */
    public function testTrackIP()
    {
        $user = $this->createUser();
        $this->runWithUser(function () use ($user) {
            $userModel = $this->container()->get(\UserModel::class);

            // Test recording an ipv4 address
            \Gdn::request()->setIP("5.6.7.8");
            $this->api()->post("tick");
            $ips = $userModel->getIPs($user["userID"]);
            $this->assertCount(1, $ips);
            $this->assertSame("5.6.7.8", $ips[0]);

            // Test recording an ipv6 address
            \Gdn::request()->setIP("2001:0db8:85a3:0000:0000:8a2e:0370:7334");
            $this->api()->post("tick");
            $ips = $userModel->getIPs($user["userID"]);
            $this->assertCount(2, $ips);
            $this->assertSame("2001:db8:85a3::8a2e:370:7334", $ips[1]); // shortened ipv6 format

            // Test recording an invalid ip address. Nothing happens.
            \Gdn::request()->setIP("invalidip");
            $this->api()->post("tick");
            $ips = $userModel->getIPs($user["userID"]);
            $this->assertCount(2, $ips);
        }, $user);
    }
}
