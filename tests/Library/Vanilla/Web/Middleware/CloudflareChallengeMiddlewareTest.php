<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\Library\Vanilla\Web\Middleware;

use Vanilla\CurrentTimeStamp;
use Vanilla\Web\Middleware\CloudflareChallengeMiddleware;
use VanillaTests\SiteTestCase;
use VanillaTests\UsersAndRolesApiTestTrait;

class CloudflareChallengeMiddlewareTest extends SiteTestCase
{
    use UsersAndRolesApiTestTrait;

    /**
     * Provides test data for `testCloudflareChallengeHeader`.
     *
     * @return \Generator
     */
    public function provideCloudflareChallengeHeaderTestData(): \Generator
    {
        // Only new unverified members get the challenge.
        yield "new unverified member" => ["-6 days", false, [], true];

        // Old unverified and new verified members don't get the challenge.
        yield "old unverified member" => ["-8 days", false, [], false];
        yield "new verified member" => ["-6 days", true, [], false];

        // Users with the proper permission never get the challenge.
        yield "new unverified moderator" => ["-6 days", false, ["community.moderate" => true], false];
        yield "new unverified admin" => ["-6 days", false, ["site.manage" => true], false];
    }

    /**
     * Tests that the response for both legacy dispatcher and new dispatcher contains the cloudflare challenge
     * header depending on various conditions.
     *
     * @param string $mockRegistrationTime
     * @param bool $verified
     * @param array $permissions
     * @param bool $isHeaderExpected
     * @return void
     * @dataProvider provideCloudflareChallengeHeaderTestData
     */
    public function testCloudflareChallengeHeader(
        string $mockRegistrationTime,
        bool $verified,
        array $permissions,
        bool $isHeaderExpected
    ) {
        CurrentTimeStamp::mockTime($mockRegistrationTime);
        $this->createRole([
            "permissions" => [
                [
                    "type" => "global",
                    "permissions" => $permissions + [
                        "session.valid" => true,
                    ],
                ],
            ],
        ]);
        $user = $this->createUser(["bypassSpam" => $verified, "roleID" => [$this->lastRoleID]]);
        CurrentTimeStamp::clearMockTime();

        $this->runWithUser(function () use ($isHeaderExpected) {
            $this->runWithConfig(["premoderation.challengeNewUsers" => true], function () use ($isHeaderExpected) {
                // Test legacy dispatcher/controller.
                $controller = $this->bessy()->get("/");
                $this->assertInstanceOf(\Gdn_Controller::class, $controller);
                $headers = \Gdn::dispatcher()->getSentHeaders();
                $this->assertCloudflareChallengeHeader($headers, $isHeaderExpected);

                // Test new dispatcher using APIv2 call.
                $response = $this->api()->get("users/me");
                $headers = $response->getHeaders();
                $this->assertCloudflareChallengeHeader($headers, $isHeaderExpected);
            });
        }, $user);
    }

    /**
     * Asserts if headers array has or does not have the cloudflare challenge header.
     *
     * @param array $headers
     * @param bool $isHeaderExpected
     * @return void
     */
    private function assertCloudflareChallengeHeader(array $headers, bool $isHeaderExpected): void
    {
        $headers = array_change_key_case($headers);
        [$expectedKey] = CloudflareChallengeMiddleware::CF_CHALLENGE_HEADER;
        if ($isHeaderExpected) {
            $this->assertArrayHasKey($expectedKey, $headers);
        } else {
            $this->assertArrayNotHasKey($expectedKey, $headers);
        }
    }
}
