<?php
/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Scheduler\Job;

use InvalidArgumentException;
use Iterator;
use Vanilla\Scheduler\Job\ExecuteIteratorJob;
use VanillaTests\BootstrapTestCase;

/**
 * Verify basic behavior of ExecuteIteratorJob.
 */
class ExecuteIteratorJobTest extends BootstrapTestCase {

    // Where we'll be storing the valid iterator class.
    private const ITERATOR_RULE = "@@iterator";

    // The method on our iterator class used to return a valid iterator.
    private const ITERATOR_METHOD = "yield";

    /** @var ExecuteIteratorJob */
    private $job;

    /**
     * @inheritDoc
     */
    public function setUp(): void {
        parent::setUp();

        $iteratorClass = new class {
            /**
             * Iterate over an array, yielding its values, and tracking each item seen.
             *
             * @param array $seen
             * @param array $items
             * @return Iterator
             */
            public function yield(array &$seen, array $items): Iterator {
                foreach ($items as $i) {
                    $seen[] = $i;
                    yield $i;
                }
            }
        };
        $this->container()->setInstance(self::ITERATOR_RULE, $iteratorClass);

        $this->job = $this->container()->get(ExecuteIteratorJob::class);
    }

    /**
     * Verify basic traversal of an iterator.
     */
    public function testSimple(): void {
        $seen = [];
        $items = [
            "foo",
            "bar",
            "baz",
        ];

        $this->job->setMessage([
            ExecuteIteratorJob::OPT_CLASS => self::ITERATOR_RULE,
            ExecuteIteratorJob::OPT_METHOD => self::ITERATOR_METHOD,
            ExecuteIteratorJob::OPT_ARGS => [&$seen, $items],
        ]);
        $this->job->run();
        $this->assertSame($items, $seen);
    }

    /**
     * Verify proper exception is thrown when the specified object does not have the specified method.
     */
    public function testBadMethod(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Method does not exist");

        $this->container()->setInstance(__FUNCTION__, new \stdClass());
        $this->job->setMessage([
            ExecuteIteratorJob::OPT_CLASS => __FUNCTION__,
            ExecuteIteratorJob::OPT_METHOD => "foo",
            ExecuteIteratorJob::OPT_ARGS => [],
        ]);
        $this->job->run();
    }

    /**
     * Verify the proper exception is thrown when receiving an invalid iterator.
     */
    public function testBadIterator(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("A valid iterator was not returned");

        $this->container()->setInstance(__FUNCTION__, new class {

            /**
             * Well, it ain't providing an iterator.
             *
             * @return bool
             */
            public function foo(): bool {
                return true;
            }
        });
        $this->job->setMessage([
            ExecuteIteratorJob::OPT_CLASS => __FUNCTION__,
            ExecuteIteratorJob::OPT_METHOD => "foo",
            ExecuteIteratorJob::OPT_ARGS => [],
        ]);
        $this->job->run();
    }
}
