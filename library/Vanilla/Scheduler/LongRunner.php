<?php
/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Scheduler;

use Garden\Web\Data;
use Garden\Web\Exception\ForbiddenException;
use Garden\Web\Exception\ServerException;
use Psr\Container\ContainerInterface;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Scheduler\Descriptor\NormalJobDescriptor;
use Vanilla\Scheduler\Job\LongRunnerJob;
use Vanilla\Scheduler\Job\JobPriority;
use Vanilla\Utility\ModelUtils;
use Vanilla\Web\SystemCallableInterface;
use Vanilla\Web\SystemTokenUtils;

/**
 * This class provides utility methods for executing longer running tasks that use iterators to break up their work.
 */
class LongRunner implements SystemCallableInterface
{
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

    /** @var ConfigurationInterface */
    private $config;

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
     * DI.
     *
     * @param ContainerInterface $container
     * @param SystemTokenUtils $tokenUtils
     * @param \Gdn_Session $session
     * @param ConfigurationInterface $config
     */
    public function __construct(
        ContainerInterface $container,
        SystemTokenUtils $tokenUtils,
        \Gdn_Session $session,
        ConfigurationInterface $config
    ) {
        $this->container = $container;
        $this->tokenUtils = $tokenUtils;
        $this->session = $session;
        $this->config = $config;
    }

    public static function getSystemCallableMethods(): array
    {
        return ["composeMultipleActions"];
    }

    /**
     * Run a long-running job and prepare it's output for the API.
     *
     * @param LongRunnerAction $action The action to run.
     *
     * @return Data API response data.
     */
    public function runApi(LongRunnerAction $action): Data
    {
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
    public function runDeferred(LongRunnerAction $action): TrackingSlipInterface
    {
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
    public function runImmediately(LongRunnerAction $action): LongRunnerResult
    {
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
    public function runIterator(LongRunnerAction $action): \Generator
    {
        $className = $action->getClassName();
        $method = $action->getMethod();
        $args = $action->getArgs();
        $options = $action->getOptions();
        // Validation.
        $this->validateLongRunnable($action);

        $generator = $this->generatorFromAction($action);
        $callableName = $action->getCallableName();

        // Start preparing our result.
        $result = new LongRunnerResult();

        // If we are resuming an existing long runner, preserve it's previous total.
        $previousTotal = $options[LongRunnerAction::OPT_PREVIOUS_TOTAL] ?? null;
        if ($previousTotal !== null) {
            $result->setCountTotalIDs($previousTotal);
        }

        // Iterate through with timeout and memory checks.
        $timeoutGenerator =
            $this->timeout >= 0 ? ModelUtils::iterateWithTimeout($generator, $this->timeout) : $generator;
        $iterations = 0;
        $stopReason = "timeout";
        foreach ($timeoutGenerator as $value) {
            if ($value instanceof LongRunnerQuantityTotal) {
                if ($previousTotal === null) {
                    // If we haven't already set a total allow it to be set here.
                    $result->setCountTotalIDs(($result->getCountTotalIDs() ?? 0) + $value->getValue());
                }
                continue;
            }

            if ($value instanceof LongRunnerItemResultInterface) {
                $result->addResult($value);
                try {
                    yield $result;
                } catch (LongRunnerTimeoutException $ex) {
                    // we ran out of time. Stop iterator and throw into the generator.
                    break;
                }
            }
            $iterations++;

            if ($this->maxIterations !== null && $iterations >= $this->maxIterations) {
                // Stop execution early.
                $stopReason = "reached max iteratons: {$this->maxIterations}";
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
                $generator->throw(new LongRunnerTimeoutException($stopReason));
            } catch (LongRunnerTimeoutException $e) {
                // The generator doesn't have any specific handling for timeouts.
                // We can call it with the same arguments.
                $result->setCallbackPayload($action->asCallbackPayload($this->tokenUtils));
                return $result;
            }

            $nextArgs = $this->extractNextArgs($action, $generator);
            if ($nextArgs === null) {
                // We actually called the runner on it's last iteration. It's actually done.
                return $result;
            }

            $result->setCallbackPayload($newAction->applyNextArgs($nextArgs)->asCallbackPayload($this->tokenUtils));
        }

        return $result;
    }

    /**
     * Given a running generator, signal to it to return its next arguments and return them.
     *
     * @param LongRunnerAction $action
     * @param \Generator $generator
     *
     * @return LongRunnerNextArgs|null The next args or null if the generator is finished.
     */
    public function extractNextArgs(LongRunnerAction $action, \Generator $generator): ?LongRunnerNextArgs
    {
        $callableName = $action->getCallableName();
        if ($generator->valid()) {
            throw new ServerException(
                "Long running method $callableName was told return it's next arguments, but did not return."
            );
        }

        $nextArgs = $generator->getReturn();
        if ($nextArgs === self::FINISHED) {
            // We actually called the runner on it's last iteration. It's actually done.
            return null;
        }

        if (!$nextArgs instanceof LongRunnerNextArgs) {
            throw new ServerException(
                "Long running method $callableName did not return a LongRunnerNextArgs when requested."
            );
        }
        return $nextArgs;
    }

    /**
     * Create a generator from an action.
     *
     * @param LongRunnerAction $action
     *
     * @return \Generator
     */
    private function generatorFromAction(LongRunnerAction $action)
    {
        // Instantiate and create the generator.
        $obj = $this->container->get($action->getClassName());
        $fn = [$obj, $action->getMethod()];
        $generator = $fn(...$action->getArgs());

        // Make sure we actually got a generator.
        if (!$generator instanceof \Generator) {
            throw new ServerException("Long running method {$action->getCallableName()} must return a \Generator.");
        }
        return $generator;
    }

    /**
     * Compose multiple actions together.
     *
     * @param LongRunnerAction[] $actions
     * @param int|null $resumeIndex
     * @param array|null $resumeArgs Arguments to resume the resumed action with.
     *
     * @return \Generator
     */
    public function composeMultipleActions(
        array $actions,
        ?int $resumeIndex = null,
        ?array $resumeArgs = null
    ): \Generator {
        $resumeIndex = $resumeIndex ?? 0;
        /** @var \Generator[] $generators */
        $generators = [];
        for ($i = 0; $i < count($actions); $i++) {
            $action = $actions[$i] ?? null;
            if ($action === null) {
                throw new ServerException("Invalid index $i for composing multiple actions");
            }

            if (is_array($action)) {
                $action = LongRunnerAction::fromJson($action);
            }
            if ($i === $resumeIndex && $resumeArgs !== null && $action instanceof LongRunnerAction) {
                $action = $action->applyNextArgs(new LongRunnerNextArgs($resumeArgs));
            }

            $generators[] = $this->generatorFromAction($action);
        }

        // Try to extract a common total quantity while running the first one.
        if ($resumeIndex === 0) {
            try {
                yield new LongRunnerQuantityTotal(function () use ($generators) {
                    $total = 0;
                    foreach ($generators as $generator) {
                        $yielded = $generator->current();
                        if ($yielded instanceof LongRunnerQuantityTotal) {
                            $total += $yielded->getValue();
                        }
                    }
                    return $total;
                });
            } catch (LongRunnerTimeoutException $timeoutException) {
                // The next time we resume, our timeout will be cached.
                return new LongRunnerNextArgs([$actions]);
            }
        }

        for (; $resumeIndex < count($generators); $resumeIndex++) {
            $generator = $generators[$resumeIndex];
            if (!$generator->valid()) {
                continue;
            }
            foreach ($generator as $iterationResult) {
                try {
                    if ($iterationResult instanceof LongRunnerQuantityTotal) {
                        // We already handled these.
                        continue;
                    } else {
                        yield $iterationResult;
                    }
                } catch (LongRunnerTimeoutException $timeoutException) {
                    if ($generator->valid()) {
                        $generator->throw($timeoutException);
                    }
                    $nextResumeArgs = $this->extractNextArgs($action, $generator);
                    if ($nextResumeArgs === null) {
                        // It finished
                        if ($resumeIndex === count($actions) - 1) {
                            // We totally finished.
                            return self::FINISHED;
                        } else {
                            return new LongRunnerNextArgs([$actions, $resumeIndex + 1]);
                        }
                    } else {
                        return new LongRunnerNextArgs([$actions, $resumeIndex, $nextResumeArgs]);
                    }
                }
            }
        }
        return self::FINISHED;
    }

    /**
     * Validate that a long-running method is callable by the system.
     *
     * @param LongRunnerAction $action The action to run.
     *
     * @throws ServerException Thrown if the method is not allowed to be run.
     * @throws ForbiddenException Thrown if there is no session user.
     */
    private function validateLongRunnable(LongRunnerAction $action)
    {
        if (!$this->session->isValid() && $this->config->get("Garden.Installed", false)) {
            throw new ForbiddenException("You must be signed in to trigger a long-running action.");
        }

        // Maybe this class only exists as a container rule?
        /** @var SystemCallableInterface $class */
        $class = $this->container->get($action->getClassName());
        if (!$class instanceof SystemCallableInterface) {
            throw new ServerException(
                "Class does not implement " . SystemCallableInterface::class . ": {$action->getClassName()}"
            );
        }

        $allowedMethods = $class::getSystemCallableMethods();
        $lowercased = array_map("strtolower", $allowedMethods);
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
    public function setMode(string $mode): LongRunner
    {
        $this->mode = $mode;
        return $this;
    }

    /**
     * @return string
     */
    public function getMode(): string
    {
        return $this->mode;
    }

    /**
     * Set a timeout for the LongRunner execution.
     *
     * @param int $timeout
     *
     * @return $this
     */
    public function setTimeout(int $timeout): LongRunner
    {
        $this->timeout = $timeout;
        return $this;
    }

    /**
     * @return int
     */
    public function getTimeout(): int
    {
        return $this->timeout;
    }

    /**
     * Set the maximum amount of iterations to do in a job.
     *
     * @param int|null $maxIterations
     *
     * @return $this
     */
    public function setMaxIterations(?int $maxIterations): LongRunner
    {
        $this->maxIterations = $maxIterations;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getMaxIterations(): ?int
    {
        return $this->maxIterations;
    }

    /**
     * Reset the long runner timeout and mode.
     *
     * @return $this
     */
    public function reset(): LongRunner
    {
        $this->mode = self::MODE_SYNC;
        $this->timeout = self::TIMEOUT_MAX;
        $this->maxIterations = null;
        return $this;
    }
}
