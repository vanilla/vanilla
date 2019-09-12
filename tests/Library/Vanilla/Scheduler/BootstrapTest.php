<?php
/**
 * @author Eduardo Garcia Julia <eduardo.garciajulia@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

declare(strict_types=1);

/**
 * Class BootstrapTest
 */
final class BootstrapTest extends \PHPUnit\Framework\TestCase {

    public function test_SchedulerInjection_WithMissingRule_Expect_NotFoundException() {

        $container = new Garden\Container\Container();

        $this->assertTrue($container != null);

        $this->expectException(\Garden\Container\NotFoundException::class);
        $this->expectExceptionMessage('Class Vanilla\Scheduler\SchedulerInterface does not exist.');

        $container->get(\Vanilla\Scheduler\SchedulerInterface::class);
    }

    public function test_SchedulerInjection_WithMissingDependencies_Expect_MissingArgumentException() {

        $container = (new Garden\Container\Container())
            ->rule(\Vanilla\Scheduler\SchedulerInterface::class)
            ->setClass(\Vanilla\Scheduler\DummyScheduler::class)
            ->setShared(true)
        ;

        $this->assertTrue($container != null);

        $this->expectException(\Garden\Container\MissingArgumentException::class);
        $this->expectExceptionMessage('Missing argument $container for Vanilla\Scheduler\DummyScheduler::__construct().');

        $container->get(\Vanilla\Scheduler\SchedulerInterface::class);
    }

    public function test_SchedulerInjection_WithMissingLogger_Expect_MissingArgumentException() {
        $container = new Garden\Container\Container();
        $container
            ->setInstance(\Psr\Container\ContainerInterface::class, $container)
            //
            ->rule(\Garden\EventManager::class)
            ->setShared(true)
            //
            ->rule(\Vanilla\Scheduler\SchedulerInterface::class)
            ->setClass(\Vanilla\Scheduler\DummyScheduler::class)
            ->setShared(true)
        ;

        \PHPUnit\Framework\Assert::assertTrue($container != null);

        $this->expectException(\Garden\Container\MissingArgumentException::class);
        $this->expectExceptionMessage('Missing argument $logger for Vanilla\Scheduler\DummyScheduler::__construct().');

        $container->get(\Vanilla\Scheduler\SchedulerInterface::class);
    }

    public function test_SchedulerInjection_WithMissingEventManager_Expect_Pass() {
        // This test will pass always because EventManager is a concrete class nor an interface
        // Container will inject a new class instance in case the class is not previously ruled inside the container
        // The only condition for this test to fail is if vanilla/vanilla is not composed-in
        $container = new Garden\Container\Container();
        $container
            ->setInstance(\Psr\Container\ContainerInterface::class, $container)
            //
            ->rule(\Psr\Log\LoggerInterface::class)
            ->setClass(\Vanilla\Logger::class)
            ->setShared(true)
            //
            ->rule(\Vanilla\Scheduler\SchedulerInterface::class)
            ->setClass(\Vanilla\Scheduler\DummyScheduler::class)
            ->setShared(true)
        ;

        $this->assertTrue($container != null);
        $this->assertNotNull($container->get(\Vanilla\Scheduler\SchedulerInterface::class));
    }

    /**
     * @return \Vanilla\Scheduler\DummyScheduler
     * @throws \Garden\Container\ContainerException
     * @throws \Garden\Container\NotFoundException
     */
    public function test_SchedulerInjection_Expect_Pass() {
        $container = new Garden\Container\Container();
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

        $this->assertTrue($container != null);

        $dummyScheduler = $container->get(\Vanilla\Scheduler\SchedulerInterface::class);

        $this->assertTrue(get_class($dummyScheduler) == \Vanilla\Scheduler\DummyScheduler::class);

        return $dummyScheduler;
    }

    /**
     * @depends test_SchedulerInjection_Expect_Pass
     *
     * @param \Vanilla\Scheduler\SchedulerInterface $dummyScheduler
     */
    public function test_SetDriver_Expect_Pass(\Vanilla\Scheduler\SchedulerInterface $dummyScheduler) {

        $bool = $dummyScheduler->addDriver(\Vanilla\Scheduler\Driver\LocalDriver::class);
        $this->assertTrue($bool);
    }

    /**
     * @depends test_SchedulerInjection_Expect_Pass
     *
     * @param \Vanilla\Scheduler\SchedulerInterface $dummyScheduler
     */
    public function test_SetDispatchEventName_Expect_Pass(\Vanilla\Scheduler\SchedulerInterface $dummyScheduler) {

        $bool = $dummyScheduler->setDispatchEventName('dispatchEvent');
        $this->assertTrue($bool);
    }

    /**
     * @depends test_SchedulerInjection_Expect_Pass
     *
     * @param \Vanilla\Scheduler\SchedulerInterface $dummyScheduler
     */
    public function test_SetDispatchedEventName_Expect_Pass(\Vanilla\Scheduler\SchedulerInterface $dummyScheduler) {

        $bool = $dummyScheduler->setDispatchedEventName('dispatchedEvent');
        $this->assertTrue($bool);
    }
}
