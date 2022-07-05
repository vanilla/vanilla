<?php
/**
 * @author Eduardo Garcia Julia <eduardo.garciajulia@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Scheduler;

use Garden\Container\Container;
use Gdn_Configuration;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Vanilla\Logger;
use Vanilla\Scheduler\Driver\LocalDriver;
use Vanilla\Scheduler\DeferredScheduler;
use Vanilla\Scheduler\SchedulerInterface;
use VanillaTests\EventSpyTestTrait;
use VanillaTests\SetsGeneratorTrait;
use VanillaTests\SiteTestCase;

/**
 * Class SchedulerTestCase
 */
class SchedulerTestCase extends SiteTestCase
{
    use SetsGeneratorTrait;
    use EventSpyTestTrait;

    /**
     * Use the dummy/deferred scheduler instead of the usual instant scheduler in tests.
     *
     * @param Container $container
     */
    public static function configureContainerBeforeStartup(Container $container)
    {
        $container
            ->rule(SchedulerInterface::class)
            ->setClass(DeferredScheduler::class)
            ->setShared(true);
    }

    /**
     * Cleanup some singletons between tests.
     */
    public function setUp(): void
    {
        parent::setUp();
        // Enable caching and clear out our instance.
        self::enableCaching();
        self::container()->setInstance(SchedulerInterface::class, null);

        // Clear the scheduler between tests.
        /** @var DeferredScheduler $scheduler */
        $scheduler = self::container()->get(SchedulerInterface::class);
        $scheduler->reset();

        /** @var Gdn_Configuration $config */
        $config = self::container()->get(Gdn_Configuration::class);
        $config->saveToConfig("Garden.Scheduler.CronMinimumTimeSpan", 0);

        /** @var SchedulerInterface $deferredScheduler */
        $deferredScheduler = self::container()->get(SchedulerInterface::class);

        $deferredScheduler->addDriver(LocalDriver::class);
    }

    /**
     * Get a new container instance with Logger.
     *
     * @return Container
     */
    protected function getEmptyContainer()
    {
        $container = new Container();
        $container
            ->setInstance(ContainerInterface::class, $container)
            //
            ->rule(LoggerInterface::class)
            ->setClass(Logger::class)
            ->setShared(true);

        return $container;
    }

    /**
     * @return DeferredScheduler
     */
    public function getDeferredScheduler(): DeferredScheduler
    {
        $deferredScheduler = $this->container()->get(SchedulerInterface::class);
        return $deferredScheduler;
    }
}
