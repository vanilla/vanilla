<?php
/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library;

use Garden\Web\Data;
use Iterator;
use Vanilla\LongRunner;
use Vanilla\Scheduler\Job\ExecuteIteratorJob;
use Vanilla\Scheduler\SchedulerInterface;
use Vanilla\Web\SystemTokenUtils;
use VanillaTests\BootstrapTestCase;
use VanillaTests\Fixtures\Scheduler\InstantScheduler;

/**
 * Verify basic behavior of LongRunner.
 */
class LongRunnerTest extends BootstrapTestCase {

    /** @var LongRunner */
    private $longRunner;

    /** @var InstantScheduler */
    private $scheduler;

    /**
     * @inheritDoc
     */
    public function setUp(): void {
        parent::setUp();

        $this->container()->call(function (LongRunner $longRunner, SchedulerInterface $scheduler) {
            $this->longRunner = $longRunner;
            $this->scheduler = $scheduler;
        });
    }

    /**
     * Assert a job response matches the expected state.
     *
     * @param string $class
     * @param string $method
     * @param array $args
     * @param Data $actual
     */
    private function assertJobRunResponse(string $class, string $method, array $args, Data $actual): void {
        $tokenUtils = $this->container()->get(SystemTokenUtils::class);

        $this->assertSame(202, $actual->getStatus());
        $this->assertSame(202, $actual->getDataItem("status"));
        $this->assertSame("incomplete", $actual->getDataItem("statusType"));

        $expectedJwt = $tokenUtils->encode([
            "method" => "{$class}::{$method}",
            "args" => $args,
        ]);
        $this->assertSame($expectedJwt, $actual->getDataItem(SystemTokenUtils::CLAIM_REQUEST_BODY));
    }

    /**
     * Verify ability to successfully generate an "incomplete" job response using makeJobRunResponse.
     */
    public function testMakeJobRunResponse(): void {
        $class = "foo";
        $method = "bar";
        $args = ["hello", "world"];

        $actual = $this->longRunner->makeJobRunResponse($class, $method, $args);
        $this->assertJobRunResponse($class, $method, $args, $actual);
    }

    /**
     * Verify executing an iterator via a scheduled job.
     */
    public function testRunApiJob(): void {
        $className = "@@" . __FUNCTION__;
        $method = "getIterator";
        $args = ["foo" => "bar"];

        $this->container()->setInstance("@@" . __FUNCTION__, new class() {

            /**
             * Gimme an iterator.
             *
             * @return Iterator
             */
            public function getIterator(): Iterator {
                yield true;
            }
        });

        $actual = $this->longRunner->runApi(
            $className,
            $method,
            $args,
            [LongRunner::OPT_LOCAL_JOB => true]
        );

        $this->assertSame(204, $actual->getStatus());

        $this->scheduler->assertJobScheduled(ExecuteIteratorJob::class, [
            ExecuteIteratorJob::OPT_CLASS => $className,
            ExecuteIteratorJob::OPT_METHOD => $method,
            ExecuteIteratorJob::OPT_ARGS => $args,
        ]);
    }

    /**
     * Verify result when an iterator does not successfully complete.
     */
    public function testRunApiTimeoutIncomplete(): void {
        $className = "@@" . __FUNCTION__;
        $method = "getIterator";
        $args = ["foo" => "bar"];

        $this->container()->setInstance("@@" . __FUNCTION__, new class() {

            /**
             * Gimme an iterator.
             *
             * @return Iterator
             */
            public function getIterator(): Iterator {
                yield "foo";
                yield "bar";
            }
        });

        $actual = $this->longRunner->runApi(
            $className,
            $method,
            $args,
            [
                LongRunner::OPT_LOCAL_JOB => false,
                LongRunner::OPT_TIMEOUT => 0,
            ]
        );

        $this->assertJobRunResponse($className, $method, $args, $actual);
    }

    /**
     * Verify behavior when an iterator is fully executed.
     */
    public function testRunApiTimeoutComplete(): void {
        $className = "@@" . __FUNCTION__;
        $method = "getIterator";
        $args = ["foo" => "bar"];

        $this->container()->setInstance("@@" . __FUNCTION__, new class() {

            /**
             * Gimme an iterator.
             *
             * @return Iterator
             */
            public function getIterator(): Iterator {
                yield "foo";
            }
        });

        $actual = $this->longRunner->runApi(
            $className,
            $method,
            $args,
            [
                LongRunner::OPT_LOCAL_JOB => false,
                LongRunner::OPT_TIMEOUT => 30,
            ]
        );

        $this->assertSame(204, $actual->getStatus());
        $this->assertSame(null, $actual->getData());
    }
}
