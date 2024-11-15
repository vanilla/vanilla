<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Forum;

use Vanilla\Community\Events\DiscussionStatusEvent;
use Vanilla\Contracts\ConfigurationInterface;
use VanillaTests\EventSpyTestTrait;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\Models\TestDiscussionModelTrait;
use VanillaTests\SiteTestCase;

/**
 * Test discussion status events.
 */
class RecordStatusEventTest extends SiteTestCase
{
    use EventSpyTestTrait;
    use CommunityApiTestTrait;

    //    use SpyingAnalyticsTestTrait;
    use EventSpyTestTrait;
    use TestDiscussionModelTrait;

    public function setUp(): void
    {
        parent::setUp();
        $config = $this->container()->get(ConfigurationInterface::class);
        $config->set("triage.enabled", true);
    }

    /**
     * Test that when an event's internal status is changed, the status change event
     * payload's status object is the internal status.
     *
     * @return void
     */
    public function testInternalStatusEvent(): void
    {
        $discussion = $this->createDiscussion();
        $statuses = $this->api()
            ->get("/discussions/statuses")
            ->getBody();

        $statuses = array_column($statuses, null, "name");

        $this->getEventManager()->clearDispatchedEvents();

        // Update the internal status (to "Resolved").
        $this->api()->put("/discussions/{$discussion["discussionID"]}/status", [
            "statusID" => $statuses["Resolved"]["statusID"],
        ]);

        $events = $this->getEventManager()->getDispatchedEvents();

        $event = null;

        foreach ($events as $e) {
            if ($e instanceof DiscussionStatusEvent) {
                $event = $e;
                break;
            }
        }

        // The event was found.
        $this->assertNotNull($event);

        // The event payload's status object reflects the internal status (i.e., the status that was changed).
        $this->assertSame($statuses["Resolved"]["statusID"], $event->getPayload()["status"]["statusID"]);
    }
}
