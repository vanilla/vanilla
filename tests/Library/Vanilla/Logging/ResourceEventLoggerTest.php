<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Logging;

use Garden\Events\ResourceEvent;
use PHPUnit\Framework\TestCase;
use Vanilla\Community\Events\CommentEvent;
use Vanilla\Logging\ResourceEventLogger;
use Psr\Log\LoggerInterface;
use Vanilla\Logger;
use VanillaTests\Library\Vanilla\TestLogger;

/**
 * Test for the ResourceEventLogger.
 */
class ResourceEventLoggerTest extends TestCase {

    /** @var ResourceEventLogger */
    private $eventLogger;

    /** @var LoggerInterface */
    private $logger;

    /**
     * @inheritDoc
     */
    public function setUp(): void {
        $parentLogger = new Logger();
        $this->logger = new TestLogger($parentLogger);

        $this->eventLogger = new ResourceEventLogger($this->logger);
    }

    /**
     * Verify adding a generic action event.
     */
    public function testIncludeAction(): void {
        $newAction = md5(rand());
        $event = new CommentEvent($newAction, []);

        $this->eventLogger->logResourceEvent($event);
        $this->assertSame([null, null, null], $this->logger->last);

        $this->eventLogger->includeAction("*", $newAction);
        $this->eventLogger->logResourceEvent($event);
        $this->assertNotNull($this->logger->last[0]);
    }

    /**
     * Verify adding a generic action event.
     */
    public function testIncludeActionReturn(): void {
        $action = md5(rand());
        $newResult = $this->eventLogger->includeAction("*", $action);
        $this->assertTrue($newResult);
        $existingResult = $this->eventLogger->includeAction("*", $action);
        $this->assertFalse($existingResult);
    }

    /**
     * Verify adding an event-specific action rule.
     */
    public function testOverrideEventAction(): void {
        $this->eventLogger->includeAction("*", ResourceEvent::ACTION_UPDATE);
        $this->eventLogger->excludeAction(CommentEvent::class, ResourceEvent::ACTION_UPDATE);

        $event = new CommentEvent(ResourceEvent::ACTION_UPDATE, []);
        $this->eventLogger->logResourceEvent($event);
        $this->assertSame([null, null, null], $this->logger->last);
    }

    /**
     * Verify basic ability to log resource events.
     */
    public function testBasicLog(): void {
        $action = ResourceEvent::ACTION_UPDATE;
        $payload = ["comment" => []];
        $event = new CommentEvent($action, $payload);

        $expected = [
            "info",
            "Comment updated.",
            [
                Logger::FIELD_CHANNEL => Logger::CHANNEL_APPLICATION,
                "event" => $event->getType() . "_" . $event->getAction(),
                "comment" => $payload['comment'],
                "resourceAction" => $event->getAction(),
                "resourceType" => $event->getType(),
            ]
        ];

        $this->eventLogger->logResourceEvent($event);
        $this->assertLogEventEqual($expected, $this->logger->last);
    }

    /**
     * Verify basic ability to log resource events.
     */
    public function testBasicLogWithSender(): void {
        $action = ResourceEvent::ACTION_UPDATE;
        $payload = ["comment" => []];
        $event = new CommentEvent($action, $payload, ['userID' => 123, 'name' => 'foo']);

        $expected = [
            "info",
            "Comment updated by {username}.",
            [
                Logger::FIELD_CHANNEL => Logger::CHANNEL_APPLICATION,
                "event" => $event->getType() . "_" . $event->getAction(),
                "comment" => $payload['comment'],
                "resourceAction" => $event->getAction(),
                "resourceType" => $event->getType(),
                Logger::FIELD_USERID => 123,
                Logger::FIELD_USERNAME => 'foo'
            ]
        ];

        $this->eventLogger->logResourceEvent($event);
        $this->assertLogEventEqual($expected, $this->logger->last);
    }

    /**
     * Verify removing a generic action event.
     */
    public function testExcludeAction(): void {
        $newAction = md5(rand());
        $event = new CommentEvent($newAction, []);

        $this->eventLogger->includeAction("*", $newAction);
        $this->eventLogger->logResourceEvent($event);
        $this->assertNotNull($this->logger->last[0]);

        $this->logger->last = [null, null, null];
        $this->eventLogger->excludeAction(CommentEvent::class, $newAction);
        $this->eventLogger->logResourceEvent($event);
        $this->assertSame([null, null, null], $this->logger->last);
    }

    /**
     * Verify adding a generic action event.
     */
    public function testExcludeActionReturn(): void {
        $action = md5(rand());
        $this->eventLogger->includeAction("*", $action);
        $existingResult = $this->eventLogger->excludeAction("*", $action);
        $this->assertTrue($existingResult);
        $newResult = $this->eventLogger->excludeAction("*", $action);
        $this->assertFalse($newResult);
    }

    /**
     * Compare two events and confirm they're equal.
     *
     * @param array $expected
     * @param array $actual
     */
    private function assertLogEventEqual(array $expected, array $actual) {
        $this->assertSame($expected[0], $actual[0], "Log levels not the same.");
        $this->assertSame($expected[1], $actual[1], "Log messages are not the same.");
        $this->assertEquals($expected[2], $actual[2], "Log contexts are not the same.");
    }
}
