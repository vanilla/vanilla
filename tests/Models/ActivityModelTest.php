<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Models;

use ActivityModel;
use Garden\EventManager;
use PHPUnit\Framework\TestCase;
use Vanilla\Dashboard\Events\NotificationEvent;
use VanillaTests\SiteTestTrait;

/**
 * Some basic tests for the `ActivityModel`.
 */
class ActivityModelTest extends TestCase {

    use SiteTestTrait;

    /** @var NotificationEvent */
    private $lastEvent;

    /** @var ActivityModel */
    private $model;

    /**
     * A test listener that increments the counter.
     *
     * @param NotificationEvent $e
     * @return NotificationEvent
     */
    public function handleNotificationEvent(NotificationEvent $e): NotificationEvent {
        $this->lastEvent = $e;
        return $e;
    }

    /**
     * Get a new model for each test.
     */
    public function setUp(): void {
        parent::setUp();

        $this->model = $this->container()->get(ActivityModel::class);

        // Make event testing a little easier.
        $this->container()->setInstance(self::class, $this);
        $this->lastEvent = null;

        /** @var EventManager */
        $eventManager = $this->container()->get(EventManager::class);
        $eventManager->unbindClass(self::class);
        $eventManager->addListenerMethod(self::class, "handleNotificationEvent");
    }

    /**
     * Verify a notification event is not dispatched when no notifications are pending.
     *
     * @return void
     */
    public function testNotificationEventNotDispatched(): void {
        $this->model->save([
            "ActivityUserID" => 1,
            "Body" => "Hello world.",
            "Format" => "markdown",
            "HeadlineFormat" => __FUNCTION__,
            "Notified" => ActivityModel::SENT_SKIPPED,
            "NotifyUserID" => 2
        ]);

        $this->assertNull($this->lastEvent);
    }

    /**
     * Verify notification event dispatched when adding a new notification.
     *
     * @return void
     */
    public function testNotificationEventDispatched(): void {
        $this->model->save([
            "ActivityUserID" => 1,
            "Body" => "Hello world.",
            "Format" => "markdown",
            "HeadlineFormat" => __FUNCTION__,
            "Notified" => ActivityModel::SENT_PENDING,
            "NotifyUserID" => 2
        ]);

        $this->assertInstanceOf(NotificationEvent::class, $this->lastEvent);
        $this->assertEquals("notification", $this->lastEvent->getType());
        $this->assertNull($this->lastEvent->getSender());
        $this->assertArrayHasKey("notification", $this->lastEvent->getPayload());
    }
}
