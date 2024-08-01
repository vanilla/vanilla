<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Garden;

use Garden\EventManager;
use Garden\PsrEventHandlersInterface;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use Vanilla\Contracts\Addons\EventListenerConfigInterface;
use VanillaTests\BootstrapTrait;
use VanillaTests\Fixtures\Events\TestPsrEventHandler;
use VanillaTests\Fixtures\Events\TestResourceEvent;
use VanillaTests\Fixtures\TestEvent;
use VanillaTests\Fixtures\TestEventChild;

/**
 * Tests for the event manager's PSR integration.
 */
class EventManagerPsrTest extends TestCase
{
    use BootstrapTrait;

    /**
     * @var EventListenerConfigInterface
     */
    private $config;
    /**
     * @var ListenerProviderInterface
     */
    private $provider;
    /**
     * @var EventDispatcherInterface
     */
    private $events;
    /**
     * @var TestEventChild
     */
    private $testEventChild;
    /**
     * @var TestEvent
     */
    private $testEvent;

    /**
     * @var EventManager
     */
    private $eventManager;

    /**
     * @inheritDoc
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->container()->setInstance(self::class, $this);
        $this->eventManager = new EventManager($this->container());
        $this->config = $this->eventManager;
        $this->provider = $this->eventManager;
        $this->events = $this->eventManager;

        $this->testEvent = new TestEvent();
        $this->testEventChild = new TestEventChild();
    }

    /**
     * Test a basic dispatch.
     */
    public function testConfigDispatch()
    {
        $this->config->addListenerMethod(self::class, "incEvent");
        $this->config->addListenerMethod(self::class, "incEvent");

        $this->events->dispatch($this->testEvent);
        $this->assertSame(2, $this->testEvent->getNum());
    }

    /**
     * Events should be able to stop propagation.
     */
    public function testStopPropagation()
    {
        $this->config->addListenerMethod(self::class, "incEventStop");
        $this->config->addListenerMethod(self::class, "incEvent");

        $this->events->dispatch($this->testEvent);
        $this->assertSame(1, $this->testEvent->getNum());
    }

    /**
     * A child event should be handled by it's parent listener too.
     */
    public function testEventInheritance()
    {
        $this->config->addListenerMethod(self::class, "incEvent");
        $this->config->addListenerMethod(self::class, "incEventChild");

        $this->events->dispatch($this->testEventChild);
        $this->assertSame(3, $this->testEventChild->getNum());
    }

    /**
     * An event handler without a type hint should fail.
     */
    public function testBadEventHandler()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->config->addListenerMethod(self::class, "badListener");
    }

    /**
     * A bad event listener.
     */
    public function badListener()
    {
        //
    }

    /**
     * A test listener that increments the counter.
     *
     * @param TestEvent $e
     * @return TestEvent
     */
    public function incEvent(TestEvent $e): TestEvent
    {
        $e->incNum();
        return $e;
    }

    /**
     * A test listener that increments twice.
     *
     * @param TestEventChild $e
     * @return TestEventChild
     */
    public function incEventChild(TestEventChild $e): TestEventChild
    {
        $e->incNum();
        $e->incNum();
        return $e;
    }

    /**
     * An event listener that stops propagation.
     *
     * @param TestEvent $e
     * @return TestEvent
     */
    public function incEventStop(TestEvent $e): TestEvent
    {
        $e->incNum();
        $e->stopPropagation();
        return $e;
    }

    /**
     * Test that we bind PsrEventHandlerInterface and unbind it.
     */
    public function testBindUnbindPsrEventHandler()
    {
        $this->eventManager->bindClass(TestPsrEventHandler::class);
        $event = new TestResourceEvent("test", []);
        $this->eventManager->dispatch($event);
        $this->assertTrue($event->wasHandled);

        // Unbind
        $this->eventManager->unbindClass(TestPsrEventHandler::class);
        $event = new TestResourceEvent("test", []);
        $this->eventManager->dispatch($event);
        $this->assertFalse($event->wasHandled);
    }
}
