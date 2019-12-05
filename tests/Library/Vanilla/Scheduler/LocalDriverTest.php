<?php
/**
 * @author Eduardo Garcia Julia <eduardo.garciajulia@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Vanilla\Library\Scheduler;

/**
 * Class LocalDriverTest.
 */
final class LocalDriverTest extends \PHPUnit\Framework\TestCase {

    /**
     * Test receiving a valid job.
     */
    public function testValidReceive() {
        /* @var $container \Garden\Container\Container */
        $container = $this->getNewContainer();

        /* @var $echoJob \Vanilla\Scheduler\Test\EchoJob */
        $echoJob = $container->get(\VanillaTests\Fixtures\Scheduler\EchoJob::class);

        /* @var $localDriver \Vanilla\Scheduler\Driver\LocalDriver */
        $localDriver = $container->get(\Vanilla\Scheduler\Driver\LocalDriver::class);

        $driverSlip = $localDriver->receive($echoJob);
        $this->assertNotNull($driverSlip);
    }

    /**
     * Test receiving an invalid job.
     */
    public function testInvalidReceive() {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The job class \'VanillaTests\Fixtures\Scheduler\NonDroveJob\' doesn\'t implement LocalJobInterface.');

        /* @var $container \Garden\Container\Container */
        $container = $this->getNewContainer();

        /* @var $nonDroveJob \Vanilla\Scheduler\Test\NonDroveJob */
        $nonDroveJob = $container->get(\VanillaTests\Fixtures\Scheduler\NonDroveJob::class);

        /* @var $localDriver \Vanilla\Scheduler\Driver\LocalDriver */
        $localDriver = $container->get(\Vanilla\Scheduler\Driver\LocalDriver::class);
        $localDriver->receive($nonDroveJob);
    }

    /**
     * Test executing an invalid job.
     */
    public function testExecute() {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The class `VanillaTests\Fixtures\Scheduler\NonCompliantDriverSlip` doesn\'t implement LocalDriverSlip.');

        /* @var $container \Garden\Container\Container */
        $container = $this->getNewContainer();

        $nonCompliantDriverSlip = new \VanillaTests\Fixtures\Scheduler\NonCompliantDriverSlip();

        /* @var $localDriver \Vanilla\Scheduler\Driver\LocalDriver */
        $localDriver = $container->get(\Vanilla\Scheduler\Driver\LocalDriver::class);
        $localDriver->execute($nonCompliantDriverSlip);
    }

    /**
     * Get a new container instance.
     *
     * @return \Garden\Container\Container
     */
    private function getNewContainer() {
        $container = new \Garden\Container\Container();
        $container
            ->setInstance(\Psr\Container\ContainerInterface::class, $container)
            //
            ->rule(\Psr\Log\LoggerInterface::class)
            ->setClass(\Vanilla\Logger::class)
            ->setShared(true)
        ;

        return $container;
    }
}
