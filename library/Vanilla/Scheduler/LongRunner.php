<?php
/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Scheduler;

use Garden\Web\Data;
use Garden\Web\Exception\ClientException;
use Garden\Web\Exception\ForbiddenException;
use Garden\Web\Exception\ServerException;
use Garden\Web\RequestInterface;
use Psr\Container\ContainerInterface;
use Vanilla\Scheduler\Descriptor\NormalJobDescriptor;
use Vanilla\Scheduler\Job\LongRunnerJob;
use Vanilla\Scheduler\Job\JobPriority;
use Vanilla\Utility\ModelUtils;
use Vanilla\Web\SystemCallableInterface;
use Vanilla\Web\SystemTokenUtils;

/**
 * This class provides utility methods for executing longer running tasks that use iterators to break up their work.
 */
class LongRunner {

    /** @var string A long-running generator can return this to indicate it is complete. */
    public const FINISHED = "finished";

    /**
     * Synchronous long runners hold the request open until a configured timeout.
     */
    public const MODE_SYNC = "sync";

    /**
     * Asyncronous long runners defer to the request to the scheduler and return immediately with a tracking slip.
     */
    public const MODE_ASYNC = "async";

    /**
     * Don't allow a longer timeout than 20s. Requests can only live for 30s so give a little headroom within that.
     */
    public const TIMEOUT_MAX = 20;

    /** @var ContainerInterface */
    private $container;

    /** @var SystemTokenUtils */
    private $tokenUtils;

    /** @var \Gdn_Session */
    private $session;

    /** @var string LongRunner::MODE_* */
    private $mode = self::MODE_SYNC;

    /** @var int */
    private $timeout = self::TIMEOUT_MAX;

    /**
     * Maximum amount of iterations to run. Mostly used for tests.
     *
     * @var int|null
     */
    private $maxIterations = null;

    /**
     * LongRunner constructor.
     *
     * @param ContainerInterface $container
     * @param SystemTokenUtils $tokenUtils
     * @param \Gdn_Session $session
     */
    public function __construct(
        ContainerInterface $container,
        SystemTokenUtils $tokenUtils,
        \Gdn_Session $session
    ) {
        $this->container = $container;
        $this->tokenUtils = $tokenUtils;
        $this->session = $session;
    }

    /**
     * Run a long-running job and prepare it's output for the API.
     *
     * @param LongRunnerAction $action The action to run.
     *
     * @return Data API response data.
     */
    public function runApi(LongRunnerAction $action): Data {
        if ($this->mode === self::MODE_SYNC) {
            $result = $this->runImmediately($action);
            return $result->asData();
        } else {
            $trackingSlip = $this->runDeferred($action);
            return new TrackingSlipData($trackingSlip);
        }
    }

    /**
     * Queue up a long-running method to run in a job.
     *
     * @param LongRunnerAction $action The action to run.
     *
     * @return TrackingSlipInterface A tracking slip for the queued job.
     */
    public function runDeferred(LongRunnerAction $action): TrackingSlipInterface {
        $this->validateLongRunnable($action);
        $job = new NormalJobDescriptor(LongRunnerJob::class);
        $job->setMessage([
            LongRunnerJob::OPT_CLASS => $action->getClassName(),
            LongRunnerJob::OPT_METHOD => $action->getMethod(),
            LongRunnerJob::OPT_ARGS => $action->getArgs(),
            LongRunnerJob::OPT_OPTIONS => $action->getOptions(),
        ]);
        $job->setPriority(JobPriority::normal());
        $job->setDelay(0);
        $job->setTrackingUserID($this->session->UserID);

        // Fetch the scheduler lazily, so it doesn't get instantiated too early.
        /** @var SchedulerInterface $scheduler */
        $scheduler = $this->container->get(SchedulerInterface::class);
        $trackingSlip = $scheduler->addJobDescriptor($job);
        return $trackingSlip;
    }

    /**
     * Run a long runner immediately untils it's completion or timeout.
     *
     * @param LongRunnerAction $action The action to run.
     *
     * @return LongRunnerResult
     */
    public function runImmediately(LongRunnerAction $action): LongRunnerResult {
        $generator = $this->runIterator($action);
        return ModelUtils::consumeGenerator($generator);
    }

    /**
     * Run a long-runner, yielding the current result every time it's progressed.
     *
     * @param LongRunnerAction $action The action to run.
     *
     * @return \Generator<int, LongRunnerResult, null, LongRunnerResult> The result of the long-running method.
     * @throws ServerException Thrown if the method is not allowed to be run or the long-runner doesn't behave properly.
     * @throws ForbiddenException Thrown if there is no session user.
     */
    public function runIterator(LongRunnerAction $action): \Generator {
        $className = $action->getClassName();
        $method = $action->getMethod();
        $args = $action->getArgs();
        $options = $action->getOptions();
        // Validation.
        $this->validateLongRunnable($action);

        // Instantiate and create the generator.
        $obj = $this->container->get($className);
        $fn = [$obj, $method];
        $generator = $fn(...$args);
        $callableName = "$className::$method()";

        // Make sure we actually got a generator.
        if (!$generator instanceof \Generator) {
            throw new ServerException("Long running method $callableName must return a \Generator.");
        }

        // Start preparing our result.
        $result = new LongRunnerResult();

        // If we are resuming an existing long runner, preserve it's previous total.
        $previousTotal = $options[LongRunnerAction::OPT_PREVIOUS_TOTAL] ?? null;
        if ($previousTotal !== null) {
            $result->setCountTotalIDs($previousTotal);
        }

        // Iterate through with timeout and memory checks.
        $timeoutGenerator = ModelUtils::iterateWithTimeout($generator, $this->timeout);
        $iterations = 0;
        foreach ($timeoutGenerator as $value) {
            if ($value instanceof LongRunnerQuantityTotal && $result->getCountTotalIDs() === null) {
                // If we haven't already set a total allow it to be set here.
                $result->setCountTotalIDs($value->getValue());
                continue;
            }

            if ($value instanceof LongRunnerItemResultInterface) {
                $result->addResult($value);
                yield $result;
            }
            $iterations++;

            if ($this->maxIterations !== null && $iterations >= $this->maxIterations) {
                // Stop execution early.
                break;
            }
        }
        $timeoutGeneratorSucceeded = !$timeoutGenerator->valid() && $timeoutGenerator->getReturn();
        $innerGeneratorCompleted = !$generator->valid();

        // We are finished if the timeout genrerator returned successfully or the initial generator is complete.
        $finished = $timeoutGeneratorSucceeded || $innerGeneratorCompleted;
        if (!$finished) {
            $options[LongRunnerAction::OPT_PREVIOUS_TOTAL] = $result->getCountTotalIDs();
            $newAction = new LongRunnerAction($className, $method, $args, $options);
            // Either we are low on memory or we are running out of time.
            // Get the next args and we need to continue.
            try {
                $generator->throw(new LongRunnerTimeoutException());
            } catch (LongRunnerTimeoutException $e) {
                // The generator doesn't have any specific handling for timeouts.
                // We can call it with the same arguments.
                $result->setCallbackPayload($action->asCallbackPayload($this->tokenUtils));
                return $result;
            }

            if ($generator->valid()) {
                throw new ServerException("Long running method $callableName was told return it's next arguments, but did not return.");
            }

            $nextArgs = $generator->getReturn();
            if ($nextArgs === self::FINISHED) {
                // We actually called the runner on it's last iteration. It's actually done.
                return $result;
            }

            if (!$nextArgs instanceof LongRunnerNextArgs) {
                throw new ServerException("Long running method $callableName did not return a LongRunnerNextArgs when requested.");
            }

            $result->setCallbackPayload(
                $newAction
                    ->applyNextArgs($nextArgs)
                    ->asCallbackPayload($this->tokenUtils)
            );
        }

        return $result;
    }

    /**
     * Validate that a long-running method is callable by the system.
     *
     * @param LongRunnerAction $action The action to run.
     *
     * @throws ServerException Thrown if the method is not allowed to be run.
     * @throws ForbiddenException Thrown if there is no session user.
     */
    private function validateLongRunnable(LongRunnerAction $action) {
        if (!$this->session->isValid()) {
            throw new ForbiddenException('You must be signed in to trigger a long-running action.');
        }

        // Maybe this class only exists as a container rule?
        /** @var SystemCallableInterface $class */
        $class = $this->container->get($action->getClassName());
        if (!$class instanceof SystemCallableInterface) {
            throw new ServerException("Class does not implement " . SystemCallableInterface::class . ": {$action->getClassName()}");
        }

        $allowedMethods = $class::getSystemCallableMethods();
        $lowercased = array_map('strtolower', $allowedMethods);
        if (!in_array(strtolower($action->getMethod()), $lowercased, true)) {
            throw new ServerException("Method `{$action->getMethod()}` was not marked as system callable.");
        }

        try {
            $reflection = new \ReflectionMethod($class, $action->getMethod());
        } catch (\ReflectionException $e) {
            throw new ServerException($e->getMessage());
        }

        if (!$reflection->isGenerator()) {
            throw new ServerException("Method is not a generator.");
        }
    }

    /**
     * Set the mode for the long runner.
     *
     * @param string $mode One of the LongRunner::MODE_* constants.
     *
     * @return $this
     */
    public function setMode(string $mode): LongRunner {
        $this->mode = $mode;
        return $this;
    }

    /**
     * @return string
     */
    public function getMode(): string {
        return $this->mode;
    }

    /**
     * Set a timeout for the LongRunner execution.
     *
     * @param int $timeout
     *
     * @return $this
     */
    public function setTimeout(int $timeout): LongRunner {
        $this->timeout = $timeout;
        return $this;
    }

    /**
     * Set the maximum amount of iterations to do in a job.
     *
     * @param int $maxIterations
     *
     * @return $this
     */
    public function setMaxIterations(int $maxIterations): LongRunner {
        $this->maxIterations = $maxIterations;
        return $this;
    }

    /**
     * Reset the long runner timeout and mode.
     *
     * @return $this
     */
    public function reset(): LongRunner {
        $this->mode = self::MODE_SYNC;
        $this->timeout = self::TIMEOUT_MAX;
        $this->maxIterations = null;
        return $this;
    }
}
