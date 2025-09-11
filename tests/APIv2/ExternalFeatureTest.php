<?php
/**
 * @author Olivier Lamy-Canuel <olamy-canuel@higherlogic.com>
 * @copyright 2009-2025 Higher Logic Inc.
 * @license Proprietary
 */

namespace VanillaTests\APIv2;

use Garden\Web\Exception\ForbiddenException;
use Garden\Web\Exception\ServerException;
use Vanilla\Dashboard\Models\ExternalServiceTracker;
use Vanilla\OpenAI\OpenAIClient;
use VanillaTests\SiteTestCase;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Test for the `/api/v2/external-services` endpoints.
 */
class ExternalFeatureTest extends SiteTestCase
{
    use UsersAndRolesApiTestTrait;

    public function setUp(): void
    {
        parent::setUp();
        $tracker = $this->container()->get(ExternalServiceTracker::class);

        $openAI = $this->container()->get(OpenAIClient::class);
        $tracker->registerService($openAI);
        $this->container()->setInstance(ExternalServiceTracker::class, $tracker);
    }

    /**
     * Test calling [GET] `/api/v2/external-services` to get a list of the services available.
     *
     * @return void
     */
    public function testIndex(): void
    {
        $result = $this->api()
            ->get("external-services")
            ->assertSuccess()
            ->getBody();
        $this->assertEquals(["OpenAI", "nexus-aiConversation-search"], $result);
    }

    /**
     * Test calling [GET] `/api/v2/external-services/OpenAI/status`
     *
     * @return void
     */
    public function testGetOpenAIStatus(): void
    {
        $result = $this->api()
            ->get("external-services/OpenAI/status")
            ->assertSuccess()
            ->getBody();

        $this->assertEquals(
            [
                "gpt35" => ["error" => "Missing baseUrl for azure openAI client."],
                "gpt4" => ["error" => "Missing baseUrl for azure openAI client."],
                "gpt4omini" => ["error" => "Missing baseUrl for azure openAI client."],
            ],
            $result
        );
    }

    /**
     * Test calling [GET] `/api/v2/external-services/OpenAI/status` with an invalid service.
     *
     * @return void
     */
    public function testGetInvalidService(): void
    {
        $this->expectException(ServerException::class);
        $this->expectExceptionMessage("Service not found.");
        $this->api()->get("external-services/ThisIsNotAValidService/status");
    }

    /**
     * Test calling [GET] `/api/v2/external-services` as an unauthorized user.
     *
     * @return void
     */
    public function testIndexPermission(): void
    {
        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage("Permission Problem");
        $this->createUser();

        $this->runWithUser(function () {
            $this->api()->get("external-services");
        }, $this->lastUserID);
    }

    /**
     * Test calling [GET] `/api/v2/external-services/OpenAI/status` as an unauthorized user.
     *
     * @return void
     */
    public function testGetStatusPermission(): void
    {
        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage("Permission Problem");
        $this->createUser();

        $this->runWithUser(function () {
            $this->api()->get("external-services/OpenAI/status");
        }, $this->lastUserID);
    }
}
