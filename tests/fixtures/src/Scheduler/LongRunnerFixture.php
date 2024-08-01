<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Fixtures\Scheduler;

use Vanilla\Scheduler\LongRunner;
use Vanilla\Scheduler\LongRunnerFailedID;
use Vanilla\Scheduler\LongRunnerNextArgs;
use Vanilla\Scheduler\LongRunnerQuantityTotal;
use Vanilla\Scheduler\LongRunnerSuccessID;
use Vanilla\Scheduler\LongRunnerTimeoutException;
use Vanilla\Web\SystemCallableInterface;

/**
 * Fixture for testing the long runner.
 */
class LongRunnerFixture implements SystemCallableInterface
{
    private $doneIDs = [];

    /**
     * @inheritdoc
     */
    public static function getSystemCallableMethods(): array
    {
        return ["canRunWithSameArgs", "yieldIDs", "notGenerator", "catchAndReturn", "catchAndYield", "yieldBack"];
    }

    /**
     * Get long runner count of total items to process.
     * @param array $itemsToYield
     *
     * @return int
     */
    public function getTotalCount(array $itemsToYield): int
    {
        return count($itemsToYield);
    }

    /**
     * This fixture implements a generator with no specific next args handling.
     *
     * It is assumed a generator like this can be run with the original arguments and pick up where it left off.
     *
     * @param int[] $idsToDo The IDs to yield.
     *
     * @return \Generator
     */
    public function canRunWithSameArgs(array $idsToDo): \Generator
    {
        foreach ($idsToDo as $id) {
            if (in_array($id, $this->doneIDs)) {
                continue;
            }
            $this->doneIDs[] = $id;
            yield $id;
            if (count($this->doneIDs) === 2) {
                // Make the caller run out of time.
                while (true) {
                    sleep(1);
                    yield;
                }
            }
        }
    }

    /**
     * Long-running task that yields a certain count of items sleeps and yields until done.
     *
     * @param int $successToYield The number of success ids to yield.
     * @param int $failedToYield The number of failed ids to yield.
     * @param int $successToYieldAfter The number of success ids to yield for next params.
     * @param int $failedToYieldAfter The number of failed ids to yield for next params.
     *
     * @return \Generator
     */
    public function yieldIDs(
        int $successToYield = 0,
        int $failedToYield = 0,
        int $successToYieldAfter = 0,
        int $failedToYieldAfter = 0
    ): \Generator {
        for ($i = 1; $i <= $successToYield; $i++) {
            yield new LongRunnerSuccessID($i);
        }

        for ($i = $successToYield + 1; $i <= $successToYield + $failedToYield; $i++) {
            yield new LongRunnerFailedID($i, new \Exception("Item $i failed.", 500));
        }

        if ($successToYieldAfter > 0 || $failedToYieldAfter > 0) {
            try {
                while (true) {
                    sleep(1);
                    yield;
                }
            } catch (LongRunnerTimeoutException $e) {
                return new LongRunnerNextArgs([$successToYieldAfter, $failedToYieldAfter]);
            }
        }

        return LongRunner::FINISHED;
    }

    /**
     * Long-running task that items.
     *
     * @param array $itemsToYield
     * @return \Generator
     */
    public function yieldBack(array $itemsToYield): \Generator
    {
        yield new LongRunnerQuantityTotal([$this, "getTotalCount"], [$itemsToYield]);
        foreach ($itemsToYield as $i => $toYield) {
            try {
                yield new LongRunnerSuccessID($toYield);
            } catch (LongRunnerTimeoutException $e) {
                if ($i === count($itemsToYield) + 1) {
                    return LongRunner::FINISHED;
                }
                $slice = array_values(array_slice($itemsToYield, $i + 1));
                return new LongRunnerNextArgs([$slice]);
            }
        }
        return LongRunner::FINISHED;
    }

    /**
     * This is a "long-running" task that is not system callable.
     *
     * @return \Generator
     */
    public function notSystemCallable(): \Generator
    {
        yield 1;
        yield 2;
        yield 3;
        yield 4;
    }

    /**
     * Not a generator function.
     */
    public function notGenerator()
    {
        return true;
    }

    /**
     * This generator catches the timeout but doesn't return next args.
     *
     * @param mixed $returnValue The value that should be returned after timeout.
     *
     * @return \Generator
     */
    public function catchAndReturn($returnValue): \Generator
    {
        while (true) {
            sleep(1);
            try {
                yield;
            } catch (LongRunnerTimeoutException $e) {
                return $returnValue;
            }
        }
    }

    /**
     * This generator catches the timeout but doesn't return next args.
     *
     * @param mixed $returnValue The value that should be returned after timeout.
     *
     * @return \Generator
     */
    public function catchAndYield($returnValue): \Generator
    {
        while (true) {
            sleep(1);
            try {
                yield;
            } catch (LongRunnerTimeoutException $e) {
                if ($returnValue === "yield") {
                    yield;
                }
                return $returnValue;
            }
        }
    }
}
