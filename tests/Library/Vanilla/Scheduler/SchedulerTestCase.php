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
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Logger;
use Vanilla\Scheduler\Driver\LocalDriver;
use Vanilla\Scheduler\DummyScheduler;
use Vanilla\Scheduler\SchedulerInterface;
use VanillaTests\Fixtures\NullCache;
use VanillaTests\SetsGeneratorTrait;
use VanillaTests\SiteTestTrait;

/**
 * Class SchedulerTestCase
 */
class SchedulerTestCase extends TestCase {
    use SiteTestTrait;
    use SetsGeneratorTrait;

    const DISPATCH_EVENT = 'SchedulerDispatch';
    const DISPATCHED_EVENT = 'SchedulerDispatched';

    /**
     * Get a new, cleanly-configured container.
     *
     * @return Container
     * @throws ContainerException On error.
     * @throws NotFoundException On error.
     */
    protected function getConfiguredContainer(): Container {
        $container = new Container();

        $container
            ->setInstance(ContainerInterface::class, $container)
            //
            ->rule(LoggerInterface::class)
            ->setClass(Logger::class)
            ->setShared(true)
            //
            ->rule(EventManager::class)
            ->setShared(true)
            //
            ->rule(SchedulerInterface::class)
            ->setClass(DummyScheduler::class)
            ->addCall('setFinalizeRequest', [false])
            ->setShared(true)
            // Configuration
            ->rule(Gdn_Configuration::class)
            ->setShared(true)
            ->addAlias('Config')
            ->addAlias(ConfigurationInterface::class)
            //
            ->rule('Gdn_Database')
            ->setShared(true)
            ->setConstructorArgs([new Reference(['Gdn_Configuration', 'Database'])])
            ->addAlias('Database')
            //
            ->rule(Gdn_Cache::class)
            ->setClass(NullCache::class)
        ;

        /** @var Gdn_Configuration $config */
        $config = $container->get(Gdn_Configuration::class);
        $config->loadArray([], 'voidConfig');
        $config->autoSave(false);
        $config->set('Garden.Scheduler.CronMinimumTimeSpan', 0);

        /** @var SchedulerInterface $dummyScheduler */
        $dummyScheduler = $container->get(SchedulerInterface::class);

        $bool = $dummyScheduler->addDriver(LocalDriver::class);
        $this->assertTrue($bool);

        $dummyScheduler->setDispatchEventName(self::DISPATCH_EVENT);
        $dummyScheduler->setDispatchedEventName(self::DISPATCHED_EVENT);

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
