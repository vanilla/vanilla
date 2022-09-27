<?php
/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Scheduler\Job;

use Vanilla\Scheduler\LongRunner;
use Vanilla\Scheduler\LongRunnerAction;
use Vanilla\Scheduler\LongRunnerResult;
use Vanilla\Utility\ModelUtils;

/**
 * Execute a long-runner in a job.
 */
class LongRunnerJob implements LocalJobInterface, TrackableJobAwareInterface
{
    use TrackableJobAwareTrait;
    use LongRunnerJobMessageTrait;

    public const OPT_ARGS = "args";
    public const OPT_OPTIONS = "options";
    public const OPT_CLASS = "class";
    public const OPT_METHOD = "method";

    /** @var int How many iterations should pass before tracking job progress when a long-runner process yields progress. */
    private const PROGRESS_DEBOUNCE_RATE = 5;

    /** @var LongRunner */
    private $longRunner;

    /** @var JobStatusModel */
    private $jobStatusModel;

    /**
     * DI.
     *
     * @param LongRunner $longRunner
     * @param JobStatusModel $jobStatusModel
     */
    public function __construct(LongRunner $longRunner, JobStatusModel $jobStatusModel)
    {
        $this->longRunner = $longRunner;
        $this->jobStatusModel = $jobStatusModel;
    }

    /**
     * @inheritdoc
     */
    public function run(): JobExecutionStatus
    {
        $initialTimeout = $this->longRunner->getTimeout();
        $initialMaxIterations = $this->longRunner->getMaxIterations();
        try {
            // There are no timeouts in a deferred job.
            $this->longRunner->setTimeout(-1);
            $this->longRunner->setMaxIterations(null);
            $result = ModelUtils::consumeGenerator($this->runIterator());
            return $result;
        } finally {
            $this->longRunner->setTimeout($initialTimeout);
            $this->longRunner->setMaxIterations($initialMaxIterations);
        }
    }

    /**
     * Run the job, iterating through it and yielding after each progress.
     *
     * @internal Only exposed for tests.
     */
    public function runIterator(): \Generator
    {
        $iterator = $this->longRunner->runIterator(
            new LongRunnerAction($this->class, $this->method, $this->args, $this->options)
        );

        $countResults = 0;
        /** @var LongRunnerResult|null $maybeResult */
        foreach ($iterator as $maybeResult) {
            // Report progress every 5 items actioned. This allows us to report some progress along the way.
            if ($maybeResult instanceof LongRunnerResult) {
                $countResults++;
                if ($countResults % self::PROGRESS_DEBOUNCE_RATE === 0) {
                    $progress = $maybeResult->asProgress();
                    $this->jobStatusModel->progressJob($this, $progress);
                    yield $progress;
                }
            }
        }

        /** @var LongRunnerResult $result */
        $result = $iterator->getReturn();
        return $result->asFinalJobStatus();
    }
}
