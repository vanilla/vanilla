<?php
/**
 * @author Eduardo Garcia Julia <eduardo.garciajulia@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Scheduler;

use Exception;
use Garden\Container\ContainerException;
use Garden\Container\NotFoundException;
use Garden\EventManager;
use Vanilla\Scheduler\Descriptor\NormalJobDescriptor;
use Vanilla\Scheduler\Job\JobExecutionStatus;
use Vanilla\Scheduler\Meta\SchedulerJobMeta;
use Vanilla\Scheduler\Meta\SchedulerJobMetaPruneEvent;
use Vanilla\Scheduler\Meta\SchedulerMetaDao;
use Vanilla\Scheduler\SchedulerInterface;
use VanillaTests\Fixtures\Scheduler\EchoJob;

/**
 * Class SchedulerJobMetaPruneEventTest
 */
class SchedulerJobMetaPruneEventTest extends SchedulerTestCase {

    /**
     * Setup Test
     *
     * @throws ContainerException On error.
     * @throws NotFoundException On error.
     * @throws Exception On Error.
     */
    public function setUp(): void {
        parent::setUp();

        sleep(1); // ensure all existing JobMeta are in the past

        /** @var SchedulerMetaDao $schedulerMetaDao */
        $schedulerMetaDao = $this->getConfiguredContainer()->get(SchedulerMetaDao::class);
        $schedulerMetaDao->pruneDetails(0);
        $this->assertCount(0, $schedulerMetaDao->getDetails());
    }

    /**
     * Test adding a simple Normal job.
     *
     * @throws ContainerException On error.
     * @throws NotFoundException On error.
     * @throws Exception On Error.
     */
    public function testPruneDetails() {
        $this->markTestSkipped('Implementation is stubbed out due to performance issues');
        $container = $this->getConfiguredContainer();

        /* @var $dummyScheduler SchedulerInterface */
        $dummyScheduler = $container->get(SchedulerInterface::class);
        $trackingSlip = $dummyScheduler->addJobDescriptor(new NormalJobDescriptor(EchoJob::class));
        $this->assertNotNull($trackingSlip);
        $this->assertTrue($trackingSlip->getStatus()->is(JobExecutionStatus::received()));

        sleep(1); // ensure all existing JobMeta are in the past

        /** @var SchedulerMetaDao $schedulerMetaDao */
        $schedulerMetaDao = $container->get(SchedulerMetaDao::class);
        $this->assertCount(1, $schedulerMetaDao->getDetails());
        $schedulerMetaDao->pruneDetails(0);
        $this->assertCount(0, $schedulerMetaDao->getDetails());
    }

    /**
     * Test adding a simple Normal job.
     *
     * @throws ContainerException On error.
     * @throws NotFoundException On error.
     * @throws Exception On Error.
     */
    public function testSchedulerJobMetaPruneEventIsFired() {
        $this->markTestSkipped('Implementation is stubbed out due to performance issues');
        $container = $this->getConfiguredContainer();

        /* @var $dummyScheduler SchedulerInterface */
        $dummyScheduler = $container->get(SchedulerInterface::class);
        $trackingSlip = $dummyScheduler->addJobDescriptor(new NormalJobDescriptor(EchoJob::class));
        $this->assertNotNull($trackingSlip);
        $this->assertTrue($trackingSlip->getStatus()->is(JobExecutionStatus::received()));

        /** @var $eventManager EventManager */
        $eventManager = $container->get(EventManager::class);
        $eventManager->bind(
            SchedulerJobMetaPruneEvent::class,
            function (SchedulerJobMetaPruneEvent $schedulerJobMetaPruneEvent) {
                $this->assertCount(1, $schedulerJobMetaPruneEvent->getPrunedJobMeta());
                /** @var SchedulerJobMeta $schedulerJobMeta */
                $schedulerJobMeta = $schedulerJobMetaPruneEvent->getPrunedJobMeta()[0];
                $this->assertTrue($schedulerJobMeta->getStatus()->is(JobExecutionStatus::received()));
            }
        );

        sleep(1); // ensure all existing JobMeta are in the past

        /** @var SchedulerMetaDao $schedulerMetaDao */
        $schedulerMetaDao = $container->get(SchedulerMetaDao::class);
        $this->assertCount(1, $schedulerMetaDao->getDetails());
        $schedulerMetaDao->pruneDetails(0);
        $this->assertCount(0, $schedulerMetaDao->getDetails());
    }
}
