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
use Vanilla\Scheduler\Driver\LocalDriver;
use VanillaTests\Fixtures\Scheduler\EchoJob;
use VanillaTests\Fixtures\Scheduler\NonCompliantDriverSlip;
use VanillaTests\Fixtures\Scheduler\NonDroveJob;

/**
 * Class LocalDriverTest.
 */
final class LocalDriverTest extends SchedulerTestCase {

    /**
     * Test receiving a valid job.
     *
     * @throws ContainerException On error.
     * @throws NotFoundException On error.
     * @throws Exception On error.
     */
    public function testValidReceive() {
        $container = $this->getEmptyContainer();

        /* @var $echoJob EchoJob */
        $echoJob = $container->get(EchoJob::class);

        /* @var $localDriver LocalDriver */
        $localDriver = $container->get(LocalDriver::class);

        $driverSlip = $localDriver->receive($echoJob);
        $this->assertNotNull($driverSlip);
    }

    /**
     * Test receiving an invalid job.
     *
     * @throws ContainerException On error.
     * @throws NotFoundException On error.
     */
    public function testInvalidReceive() {
        $this->expectException(Exception::class);
        $msg = 'The job class \'VanillaTests\Fixtures\Scheduler\NonDroveJob\' doesn\'t implement LocalJobInterface.';
        $this->expectExceptionMessage($msg);

        $container = $this->getEmptyContainer();

        /* @var $nonDroveJob NonDroveJob */
        $nonDroveJob = $container->get(NonDroveJob::class);

        /* @var $localDriver LocalDriver */
        $localDriver = $container->get(LocalDriver::class);
        $localDriver->receive($nonDroveJob);
    }

    /**
     * Test executing an invalid job.
     *
     * @throws ContainerException On error.
     * @throws NotFoundException On error.
     */
    public function testExecute() {
        $this->expectException(Exception::class);
        $msg = 'The class `VanillaTests\Fixtures\Scheduler\NonCompliantDriverSlip` doesn\'t implement LocalDriverSlip.';
        $this->expectExceptionMessage($msg);

        $container = $this->getEmptyContainer();

        $nonCompliantDriverSlip = new NonCompliantDriverSlip();

        /* @var $localDriver LocalDriver */
        $localDriver = $container->get(LocalDriver::class);
        $localDriver->execute($nonCompliantDriverSlip);
    }
}
