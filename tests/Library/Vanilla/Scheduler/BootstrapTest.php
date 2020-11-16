<?php
/**
 * @author Eduardo Garcia Julia <eduardo.garciajulia@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Scheduler;

use Garden\Container\Container;
use Garden\Container\ContainerException;
use Garden\Container\MissingArgumentException;
use Garden\Container\NotFoundException;
use Garden\EventManager;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Logger;
use Vanilla\Scheduler\Driver\LocalDriver;
use Vanilla\Scheduler\DummyScheduler;
use Vanilla\Scheduler\SchedulerInterface;
use VanillaTests\Fixtures\NullCache;
use Gdn_Cache;
use Gdn_Configuration;

/**
 * Class BootstrapTest
 */
final class BootstrapTest extends TestCase {

    /**
     * Test scheduler injection with missing rule throws NotFoundException.
     *
     * @throws ContainerException On error.
     * @throws NotFoundException On error.
     */
    public function testSchedulerInjectionWithMissingRule() {
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Class Vanilla\Scheduler\SchedulerInterface does not exist.');

        $container = new Container();
        $container->get(SchedulerInterface::class);
    }

    /**
     * Test scheduler injection with missing dependencies throws MissingArgumentException.
     *
     * @throws ContainerException On error.
     * @throws NotFoundException On error.
     */
    public function testSchedulerInjectionWithMissingDependencies() {
        $this->expectException(MissingArgumentException::class);
        $msg = 'Missing argument $container for Vanilla\Scheduler\DummyScheduler::__construct().';
        $this->expectExceptionMessage($msg);

        $container = (new Container())
            ->rule(SchedulerInterface::class)
            ->setClass(DummyScheduler::class)
            ->setShared(true)
        ;

        $this->expectException(MissingArgumentException::class);
        $msg = 'Missing argument $container for Vanilla\Scheduler\DummyScheduler::__construct().';
        $this->expectExceptionMessage($msg);

        $container->get(SchedulerInterface::class);
    }

    /**
     * Test scheduler injection wit missing logger throws MissingArgumentException.
     *
     * @throws ContainerException On error.
     * @throws NotFoundException On error.
     */
    public function testSchedulerInjectionWithMissingLogger() {
        $this->expectException(MissingArgumentException::class);
        $this->expectExceptionMessage('Missing argument $logger for Vanilla\Scheduler\DummyScheduler::__construct().');

        $container = new Container();
        $container
            ->setInstance(ContainerInterface::class, $container)
            ->rule(EventManager::class)
            ->setShared(true)
            ->rule(SchedulerInterface::class)
            ->setClass(DummyScheduler::class)
            ->setShared(true)
        ;

        $this->expectException(MissingArgumentException::class);
        $this->expectExceptionMessage('Missing argument $logger for Vanilla\Scheduler\DummyScheduler::__construct().');

        $container->get(SchedulerInterface::class);
    }

    /**
     * Test scheduler injection with missing event manager.
     *
     * @throws ContainerException On error.
     * @throws NotFoundException On error.
     */
    public function testSchedulerInjectionWithMissingEventManager() {
        // This test will pass always because EventManager is a concrete class nor an interface
        // Container will inject a new class instance in case the class is not previously ruled inside the container
        // The only condition for this test to fail is if vanilla/vanilla is not composed-in
        $container = new Container();
        $container
            ->setInstance(ContainerInterface::class, $container)
            //
            ->rule(LoggerInterface::class)
            ->setClass(Logger::class)
            ->setShared(true)
            //
            ->rule(Gdn_Cache::class)
            ->setClass(NullCache::class)
            //
            ->rule(ConfigurationInterface::class)
            ->setClass(Gdn_Configuration::class)
            //
            ->rule(SchedulerInterface::class)
            ->setClass(DummyScheduler::class)
            ->setShared(true)
        ;

        $this->assertNotNull($container->get(SchedulerInterface::class));
    }

    /**
     * Test scheduler injection.
     *
     * @return DummyScheduler
     * @throws NotFoundException On error.
     * @throws ContainerException On error.
     */
    public function testSchedulerInjection() {
        $container = new Container();
        $container
            ->setInstance(ContainerInterface::class, $container)
            //
            ->rule(LoggerInterface::class)
            ->setClass(Logger::class)
            ->setShared(true)
            // Not really needed
            ->rule(EventManager::class)
            ->setShared(true)
            //
            ->rule(Gdn_Cache::class)
            ->setClass(NullCache::class)
            //
            ->rule(ConfigurationInterface::class)
            ->setClass(Gdn_Configuration::class)
            //
            ->rule(SchedulerInterface::class)
            ->setClass(DummyScheduler::class)
            ->setShared(true)
        ;

        $dummyScheduler = $container->get(SchedulerInterface::class);
        $this->assertTrue(get_class($dummyScheduler) == DummyScheduler::class);

        return $dummyScheduler;
    }

    /**
     * Test addDriver.
     *
     * @param SchedulerInterface $dummyScheduler
     * @depends testSchedulerInjection
     */
    public function testSetDriver(SchedulerInterface $dummyScheduler) {
        $bool = $dummyScheduler->addDriver(LocalDriver::class);
        $this->assertTrue($bool);
    }

    /**
     * Test setDispatchEventName.
     *
     * @param SchedulerInterface $dummyScheduler
     * @depends testSchedulerInjection
     */
    public function testSetDispatchEventName(SchedulerInterface $dummyScheduler) {
        $bool = $dummyScheduler->setDispatchEventName('dispatchEvent');
        $this->assertTrue($bool);
    }

    /**
     * Test setDispatchedEventName.
     *
     * @param SchedulerInterface $dummyScheduler
     * @depends testSchedulerInjection
     */
    public function testSetDispatchedEventName(SchedulerInterface $dummyScheduler) {
        $bool = $dummyScheduler->setDispatchedEventName('dispatchedEvent');
        $this->assertTrue($bool);
    }
}
