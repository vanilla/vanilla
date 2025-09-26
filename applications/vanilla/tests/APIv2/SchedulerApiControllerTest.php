<?php
/**
 * @author Olivier Lamy-Canuel <olamy-canuel@higherlogic.com>
 * @copyright 2009-2023 Higher Logic Inc.
 * @license Proprietary
 */

namespace VanillaTests\APIv2;

use Garden\Web\Dispatcher;
use Gdn;
use SchedulerApiController;
use UserModel;
use Vanilla\Http\InternalClient;
use Vanilla\Scheduler\CronModel;
use VanillaTests\EventSpyTestTrait;

/**
 * Test around the `api/v2/scheduler/cron` endpoint.
 */
class SchedulerApiControllerTest extends AbstractAPIv2Test
{
    use EventSpyTestTrait;

    const BASE_URL = "scheduler/cron";

    /** @var UserModel */
    protected $userModel;

    /**
     * @inheritdoc
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->token = Gdn::config()->get("Garden.Scheduler.Token");
        Gdn::config()->saveToConfig([CronModel::CONF_OFFSET_SECONDS => 0]);
        $this->dispatcher = $this->container()->get(Dispatcher::class);
        $this->client = $this->container()->get(InternalClient::class);

        // Clear the CRON timeout
        Gdn::cache()->flush();
        Gdn::session()->end();
        $this->assertEquals(0, Gdn::session()->UserID);
    }

    /**
     * Test that the CRON does trigger properly and that it is calling the Jobs as the System User.
     *
     * @return void
     */
    public function testPostCron(): void
    {
        $request = $this->client->createRequest("POST", self::BASE_URL, []);
        Gdn::request()->setHeader("Authorization", "Bearer " . $this->token);

        $response = $this->dispatcher->dispatch($request);
        $this->assertEventFired(SchedulerApiController::CRON_TRIGGER_EVENT);
        $this->assertTrue($response->isSuccessful());

        // Make sure we are system.
        $this->assertEquals(Gdn::userModel()->getSystemUserID(), Gdn::session()->UserID);
    }

    /**
     * Test that the endpoints fails if no token is provided.
     *
     * @return void
     */
    public function testCronNoToken(): void
    {
        $request = $this->client->createRequest("POST", self::BASE_URL, []);
        Gdn::request()->setHeader("Authorization", "");
        $response = $this->dispatcher->dispatch($request);
        $this->assertEquals(403, $response->getStatus());
        $this->assertEquals("AdHocAuth - Missing Token", $response->getData()["description"]);
        $this->assertFalse($response->isSuccessful());
    }

    /**
     * Test that the endpoints fails if a wrong token is provided.
     *
     * @return void
     */
    public function testCronWrongToken(): void
    {
        $request = $this->client->createRequest("POST", self::BASE_URL, []);
        Gdn::request()->setHeader("Authorization", "Bearer totallyNotAValidToken");

        $response = $this->dispatcher->dispatch($request);

        $this->assertEquals(401, $response->getStatus());
        $this->assertEquals("AdHocAuth - Invalid Token", $response->getData()["description"]);
        $this->assertFalse($response->isSuccessful());
    }
}
