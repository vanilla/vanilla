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
use Vanilla\Http\InternalClient;
use VanillaTests\Fixtures\TestCache;
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

    /** @var TestCache */
    private $cache;

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

        $this->cache = self::enableCaching();
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

    /**
     * Verify sending to a nonexistent user doesn't trigger an error and doesn't dispatch an event.
     */
    public function testNotifyInvalidUser(): void {
        $this->model->save([
            "HeadlineFormat" => __FUNCTION__,
            "NotifyUserID" => 999999,
            "Notified" => ActivityModel::SENT_PENDING,
        ]);
        $this->assertNull($this->lastEvent);
    }

    /**
     * Verify a notification event is not dispatched when no notifications are pending.
     *
     * @return void
     */
    public function testNotificationCount(): void {
        $this->notifyUser(3);
        $this->notifyUser(3, ActivityModel::SENT_OK); // Already "sent".
        $this->notifyUser(4);
        $this->notifyUser(4); // Other user. Independant cache.
        // Enable caching.
        $this->assertEquals(1, $this->model->getUserTotalUnread(3));
        $this->assertEquals(2, $this->model->getUserTotalUnread(4));
        $this->model->getUserTotalUnread(3);
        $this->model->getUserTotalUnread(3);
        $this->model->getUserTotalUnread(3);

        $cacheKey = "notificationCount/users/3";
        $this->cache->assertGetCount($cacheKey, 3);
        $this->cache->assertSetCount($cacheKey, 1);
    }

    /**
     * Send a notification to a user.
     *
     * @param int $userID
     * @param int $status
     */
    private function notifyUser(int $userID, int $status = ActivityModel::SENT_PENDING) {
        $this->model->save([
            "ActivityUserID" => 1,
            "Body" => "Hello world.",
            "Format" => "markdown",
            "HeadlineFormat" => __FUNCTION__,
            "Notified" => $status,
            "NotifyUserID" => $userID, // Different userID. Should have an independent count.
        ]);
    }
}
