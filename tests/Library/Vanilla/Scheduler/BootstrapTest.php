<?php
/**
 * @author Eduardo Garcia Julia <eduardo.garciajulia@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Vanilla\Library\Scheduler;

use Garden\Container\MissingArgumentException;
use Garden\Container\NotFoundException;

/**
 * Class BootstrapTest
 */
final class BootstrapTest extends \PHPUnit\Framework\TestCase {

    /**
     * Test scheduler injection with missing rule throws NotFoundException.
     */
    public function testSchedulerInjectionWithMissingRule() {
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Class Vanilla\Scheduler\SchedulerInterface does not exist.');

        $container = new \Garden\Container\Container();
        $container->get(\Vanilla\Scheduler\SchedulerInterface::class);
    }

    /**
     * Test scheduler injection with missing dependencies throws MissingArgumentException.
     */
    public function testSchedulerInjectionWithMissingDependencies() {
        $this->expectException(MissingArgumentException::class);
        $this->expectExceptionMessage('Missing argument $container for Vanilla\Scheduler\DummyScheduler::__construct().');

        $container = (new \Garden\Container\Container())
            ->rule(\Vanilla\Scheduler\SchedulerInterface::class)
            ->setClass(\Vanilla\Scheduler\DummyScheduler::class)
            ->setShared(true)
        ;

        $this->expectException(\Garden\Container\MissingArgumentException::class);
        $this->expectExceptionMessage('Missing argument $container for Vanilla\Scheduler\DummyScheduler::__construct().');

        $container->get(\Vanilla\Scheduler\SchedulerInterface::class);
    }

    /**
     * Test scheduler injection wit missing logger throws MissingArgumentException.
     */
    public function testSchedulerInjectionWithMissingLogger() {
        $this->expectException(MissingArgumentException::class);
        $this->expectExceptionMessage('Missing argument $logger for Vanilla\Scheduler\DummyScheduler::__construct().');

        $container = new \Garden\Container\Container();
        $container
            ->setInstance(\Psr\Container\ContainerInterface::class, $container)
            ->rule(\Garden\EventManager::class)
            ->setShared(true)
            ->rule(\Vanilla\Scheduler\SchedulerInterface::class)
            ->setClass(\Vanilla\Scheduler\DummyScheduler::class)
            ->setShared(true)
        ;

        $this->expectException(\Garden\Container\MissingArgumentException::class);
        $this->expectExceptionMessage('Missing argument $logger for Vanilla\Scheduler\DummyScheduler::__construct().');

        $container->get(\Vanilla\Scheduler\SchedulerInterface::class);
    }

    /**
     * Test scheduler injection with missing event manager.
     */
    public function testSchedulerInjectionWithMissingEventManager() {
        // This test will pass always because EventManager is a concrete class nor an interface
        // Container will inject a new class instance in case the class is not previously ruled inside the container
        // The only condition for this test to fail is if vanilla/vanilla is not composed-in
        $container = new \Garden\Container\Container();
        $container
            ->setInstance(\Psr\Container\ContainerInterface::class, $container)
            ->rule(\Psr\Log\LoggerInterface::class)
            ->setClass(\Vanilla\Logger::class)
            ->setShared(true)
            ->rule(\Vanilla\Scheduler\SchedulerInterface::class)
            ->setClass(\Vanilla\Scheduler\DummyScheduler::class)
            ->setShared(true)
        ;

        $this->assertNotNull($container->get(\Vanilla\Scheduler\SchedulerInterface::class));
    }

    /**
     * Test scheduler injection.
     *
     * @return \Vanilla\Scheduler\DummyScheduler
     */
    public function testSchedulerInjection() {
        $container = new \Garden\Container\Container();
        $container
            ->setInstance(\Psr\Container\ContainerInterface::class, $container)
            //
            ->rule(\Psr\Log\LoggerInterface::class)
            ->setClass(\Vanilla\Logger::class)
            ->setShared(true)
            // Not really needed
            ->rule(\Garden\EventManager::class)
            ->setShared(true)
            ->rule(\Vanilla\Scheduler\SchedulerInterface::class)
            ->setClass(\Vanilla\Scheduler\DummyScheduler::class)
            ->setShared(true)
        ;

        $dummyScheduler = $container->get(\Vanilla\Scheduler\SchedulerInterface::class);
        $this->assertTrue(get_class($dummyScheduler) == \Vanilla\Scheduler\DummyScheduler::class);
        return $dummyScheduler;
    }

    /**
     * Test addDriver.
     *
     * @param \Vanilla\Scheduler\SchedulerInterface $dummyScheduler
     * @depends testSchedulerInjection
     */
    public function testSetDriver(\Vanilla\Scheduler\SchedulerInterface $dummyScheduler) {
        $bool = $dummyScheduler->addDriver(\Vanilla\Scheduler\Driver\LocalDriver::class);
        $this->assertTrue($bool);
    }

    /**
     * Test setDispatchEventName.
     *
     * @param \Vanilla\Scheduler\SchedulerInterface $dummyScheduler
     * @depends testSchedulerInjection
     */
    public function testSetDispatchEventName(\Vanilla\Scheduler\SchedulerInterface $dummyScheduler) {
        $bool = $dummyScheduler->setDispatchEventName('dispatchEvent');
        $this->assertTrue($bool);
    }

    /**
     * Test setDispatchedEventName.
     *
     * @param \Vanilla\Scheduler\SchedulerInterface $dummyScheduler
     * @depends testSchedulerInjection
     */
    public function testSetDispatchedEventName(\Vanilla\Scheduler\SchedulerInterface $dummyScheduler) {
        $bool = $dummyScheduler->setDispatchedEventName('dispatchedEvent');
        $this->assertTrue($bool);
    }
}
