<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Logging;

use Garden\Events\ResourceEvent;
use PHPUnit\Framework\TestCase;
use Vanilla\Community\Events\CommentEvent;
use Vanilla\Logging\EventLogger;
use Psr\Log\LoggerInterface;
use Vanilla\Logger;
use VanillaTests\Library\Vanilla\TestLogger;

/**
 * Test for the EventLogger.
 */
class EventLoggerTest extends TestCase {

    /** @var EventLogger */
    private $eventLogger;

    /** @var LoggerInterface */
    private $logger;

    /** @var \Gdn_Request|\PHPUnit\Framework\MockObject\Stub */
    private $request;

    /** @var \Gdn_Session */
    private $session;

    /**
     * @inheritDoc
     */
    public static function setUpBeforeClass(): void {
        parent::setUpBeforeClass();
        require_once __DIR__ . "/../../../../library/Vanilla/Logger.php";
        require_once __DIR__ . "/../../../../applications/vanilla/Events/CommentEvent.php";
    }

    /**
     * @inheritDoc
     */
    public function setUp(): void {
        $parentLogger = new Logger();
        $this->logger = new TestLogger($parentLogger);

        $this->session = new \Gdn_Session();
        $this->session->User = (object)[
            "UserID" => 1,
            "Name" => "Vanilla",
        ];

        $this->request = $this->createStub(\Gdn_Request::class);
        $this->request->method("getIP")->willReturn("127.0.0.1");
        $this->request->method("getPath")->willReturn("/vanilla/path/to/endpoint");
        $this->request->method("getMethod")->willReturn("POST");
        $this->request->method("urlDomain")->willReturn("https://dev.vanilla.localhost");

        $this->eventLogger = new EventLogger($this->logger, $this->session, $this->request);
    }

    /**
     * Verify adding a generic action event.
     */
    public function testAddAction(): void {
        $newAction = md5(rand());
        $event = new CommentEvent($newAction, []);

        $this->eventLogger->logResourceEvent($event);
        $this->assertSame([null, null, null], $this->logger->last);

        $this->eventLogger->addAction($newAction);
        $this->eventLogger->logResourceEvent($event);
        $this->assertNotNull($this->logger->last[0]);
    }

    /**
     * Verify adding a generic action event.
     */
    public function testAddActionReturn(): void {
        $action = md5(rand());
        $newResult = $this->eventLogger->addAction($action);
        $this->assertTrue($newResult);
        $existingResult = $this->eventLogger->addAction($action);
        $this->assertFalse($existingResult);
    }

    /**
     * Verify adding an event-specific action rule.
     */
    public function testOverrideEventAction(): void {
        $this->eventLogger->removeAction(ResourceEvent::ACTION_UPDATE);
        $this->eventLogger->overrideEventAction(CommentEvent::class, ResourceEvent::ACTION_UPDATE, true);

        $event = new CommentEvent(ResourceEvent::ACTION_UPDATE, []);
        $this->eventLogger->logResourceEvent($event);
        [$level, $message, $context] = $this->logger->last;
        $this->assertSame("comment_update", $context[Logger::FIELD_EVENT]);
    }

    /**
     * Verify basic ability to log resource events.
     */
    public function testBasicLog(): void {
        $action = ResourceEvent::ACTION_UPDATE;
        $payload = ["foo" => "bar"];
        $event = new CommentEvent($action, $payload);

        $expected = [
            "info",
            "{username} updated {resourceType}",
            [
                "event" => $event->getType() . "_" . $event->getAction(),
                "payload" => $payload,
                "resourceAction" => $event->getAction(),
                "resourceType" => $event->getType(),
                "domain" => $this->request->urlDomain(),
                "ip" => $this->request->getIP(),
                "method" => $this->request->getMethod(),
                "path" => $this->request->getPath(),
                "userID" => $this->session->User->UserID,
                "username" => $this->session->User->Name,
            ]
        ];

        $this->eventLogger->logResourceEvent($event);
        $this->assertLogEventEqual($expected, $this->logger->last);
    }

    /**
     * Verify removing a generic action event.
     */
    public function testRemoveAction(): void {
        $newAction = md5(rand());
        $event = new CommentEvent($newAction, []);

        $this->eventLogger->addAction($newAction);
        $this->eventLogger->logResourceEvent($event);
        $this->assertNotNull($this->logger->last[0]);

        $this->logger->last = [null, null, null];
        $this->eventLogger->removeAction($newAction);
        $this->eventLogger->logResourceEvent($event);
        $this->assertSame([null, null, null], $this->logger->last);
    }

    /**
     * Verify adding a generic action event.
     */
    public function testRemoveActionReturn(): void {
        $action = md5(rand());
        $this->eventLogger->addAction($action);
        $existingResult = $this->eventLogger->removeAction($action);
        $this->assertTrue($existingResult);
        $newResult = $this->eventLogger->removeAction($action);
        $this->assertFalse($newResult);
    }

    /**
     * Compare two events and confirm they're equal.
     *
     * @param array $expected
     * @param array $actual
     */
    private function assertLogEventEqual(array $expected, array $actual) {
        $this->assertEqualsCanonicalizing($expected, $actual);
    }
}
