<?php
/**
 * @author Eduardo Garcia Julia <eduardo.garciajulia@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Scheduler;

use Garden\Container\Container;
use Garden\Container\ContainerException;
use Garden\Container\NotFoundException;
use Garden\Container\Reference;
use Garden\EventManager;
use Gdn_Cache;
use Gdn_Configuration;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Vanilla\Bootstrap;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Logger;
use Vanilla\Scheduler\Driver\LocalDriver;
use Vanilla\Scheduler\DummyScheduler;
use Vanilla\Scheduler\SchedulerInterface;
use VanillaTests\EventSpyTestTrait;
use VanillaTests\Fixtures\NullCache;
use VanillaTests\SetsGeneratorTrait;
use VanillaTests\SiteTestCase;
use VanillaTests\SiteTestTrait;

/**
 * Class SchedulerTestCase
 */
class SchedulerTestCase extends SiteTestCase {
    use SetsGeneratorTrait;
    use EventSpyTestTrait;

    const DISPATCH_EVENT = 'SchedulerDispatch';
    const DISPATCHED_EVENT = 'SchedulerDispatched';

    /**
     * Use the dummy/deferred scheduler instead of the usual instant scheduler in tests.
     *
     * @param Container $container
     */
    public static function configureContainerBeforeStartup(Container $container) {
        $container
            ->rule(SchedulerInterface::class)
            ->setClass(DummyScheduler::class)
            ->addCall('setFinalizeRequest', [false])
            ->setShared(true)
        ;
    }

    /**
     * Cleanup some singletons between tests.
     */
    public function setUp(): void {
        parent::setUp();

        // Clear the scheduler between tests.
        /** @var DummyScheduler $scheduler */
        $scheduler = self::container()->get(SchedulerInterface::class);
        $scheduler->reset();
        $this->getEventManager()->unbindAll();
    }


    /**
     * Get a new, cleanly-configured container.
     *
     * @return Container
     * @throws ContainerException On error.
     * @throws NotFoundException On error.
     */
    protected function getConfiguredContainer(): Container {
        $container = self::container();

        /** @var Gdn_Configuration $config */
        $config = $container->get(Gdn_Configuration::class);
        $config->set('Garden.Scheduler.CronMinimumTimeSpan', 0);

        /** @var SchedulerInterface $dummyScheduler */
        $dummyScheduler = $container->get(SchedulerInterface::class);

        $bool = $dummyScheduler->addDriver(LocalDriver::class);
        $this->assertTrue($bool);

        return $container;
    }

    /**
     * Get a new container instance with Logger.
     *
     * @return Container
     */
    protected function getEmptyContainer() {
        $container = new Container();
        $container
            ->setInstance(ContainerInterface::class, $container)
            //
            ->rule(LoggerInterface::class)
            ->setClass(Logger::class)
            ->setShared(true)
        ;

        return $container;
    }
}
