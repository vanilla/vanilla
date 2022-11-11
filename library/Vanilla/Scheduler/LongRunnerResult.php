<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Scheduler;

use Garden\Web\Data;
use Vanilla\Scheduler\Job\JobExecutionProgress;
use Vanilla\Scheduler\Job\JobExecutionStatus;
use Vanilla\Scheduler\TrackingSlipInterface;
use Vanilla\Web\JsonView;

/**
 * Class for representing the result of a bulk action.
 */
final class LongRunnerResult implements \JsonSerializable
{
    /** @var int|null */
    private $countTotalIDs = null;

    /** @var int|string[] */
    private $successIDs = [];

    /** @var int|string[] */
    private $failedIDs = [];

    /** @var \Exception[] */
    private $exceptionsByID = [];

    /** @var string|null */
    private $callbackPayload = null;

    /**
     * Constructor.
     */
    public function __construct()
    {
        // Use the setters.
    }

    /**
     * Convert the results current state into progress.
     *
     * @return JobExecutionProgress
     */
    public function asProgress(): JobExecutionProgress
    {
        $progress = new JobExecutionProgress($this->countTotalIDs, count($this->successIDs), count($this->failedIDs));
        $errorMessage = $this->getCombinedErrorMessage();
        if ($errorMessage) {
            $progress->setErrorMessage($errorMessage);
        }
        return $progress;
    }

    /**
     * Get the result as a job exection status, assuming this is the only result in the job.
     *
     * @return JobExecutionStatus
     */
    public function asFinalJobStatus(): JobExecutionStatus
    {
        if (count($this->failedIDs) > 0) {
            return JobExecutionStatus::error();
        } else {
            return JobExecutionStatus::complete();
        }
    }

    /**
     * Get a combined error message from the errors in the job.
     *
     * @return string|null
     */
    public function getCombinedErrorMessage(): ?string
    {
        if (count($this->failedIDs) === 0) {
            return null;
        }

        $resultMessages = ["Bulk action failed for recordIDs " . implode(", ", $this->failedIDs)];
        foreach ($this->exceptionsByID as $id => $exception) {
            $resultMessages[] = "ID '$id': " . $exception->getMessage();
        }
        return implode("\n", $resultMessages);
    }

    /**
     * Get the result as some HTML.
     *
     * @return Data
     */
    public function asData(): Data
    {
        $defaultCode = $this->callbackPayload === null ? 200 : 408;
        $allPossibleCodes = [$defaultCode];
        foreach ($this->exceptionsByID as $exception) {
            $allPossibleCodes[] = $exception->getCode();
        }
        // Max always has to have at least one value.
        $maxCode = max(0, ...$allPossibleCodes);

        if (count($this->exceptionsByID) > 0 && $maxCode < 400) {
            // We had an exception with a low code (probably not an HTTP exception).
            // Likely a server side issue.
            $maxCode = 500;
        }
        return new Data($this, ["status" => $maxCode]);
    }

    /**
     * Serialize as JSON.
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        return [
            "progress" => [
                "successIDs" => $this->successIDs,
                "failedIDs" => $this->failedIDs,
                "exceptionsByID" => $this->serializeExceptions(),
                "countTotalIDs" => $this->countTotalIDs,
            ],
            "callbackPayload" => $this->callbackPayload,
        ];
    }

    /**
     * @return array|object
     */
    private function serializeExceptions()
    {
        return empty($this->exceptionsByID) ? new \stdClass() : $this->exceptionsByID;
    }

    /**
     * Add an ID with many possible result types.
     *
     * @param LongRunnerItemResultInterface|null $result One of many result types.
     *
     * @return void
     */
    public function addResult(LongRunnerItemResultInterface $result): void
    {
        if ($result instanceof LongRunnerFailedID) {
            $this->addFailedResult($result->getRecordID(), $result->getException());
        } elseif ($result instanceof LongRunnerSuccessID) {
            $this->addSuccessResult($result->getRecordID());
        }

        // Other-wise do nothing.
        // We don't know how to track it.
    }

    /**
     * Add the ID of a record that succeeded in the bulk action.
     *
     * @param int|string $successID
     */
    private function addSuccessResult($successID): void
    {
        $this->successIDs[] = $successID;
    }

    /**
     * Add the ID of a record that failed in the bulk action.
     *
     * @param int|string $failedID The ID of the record.
     * @param \Exception|null $exception Optionally an exception about why the record failed.
     */
    private function addFailedResult($failedID, \Exception $exception = null)
    {
        $this->failedIDs[] = $failedID;

        if ($exception !== null) {
            $this->exceptionsByID[$failedID] = $exception;
        }
    }

    /**
     * @return \Exception[]
     */
    public function getExceptionsByID(): array
    {
        return $this->exceptionsByID;
    }

    /**
     * @return int|string[]
     */
    public function getSuccessIDs(): array
    {
        return $this->successIDs;
    }

    /**
     * @return int|string[]
     */
    public function getFailedIDs(): array
    {
        return $this->failedIDs;
    }

    /**
     * @return int|null
     */
    public function getCountTotalIDs(): ?int
    {
        return $this->countTotalIDs;
    }

    /**
     * @param int $countTotalIDs
     */
    public function setCountTotalIDs(int $countTotalIDs): void
    {
        $this->countTotalIDs = $countTotalIDs;
    }

    /**
     * @param string $callbackPayload
     */
    public function setCallbackPayload(string $callbackPayload): void
    {
        $this->callbackPayload = $callbackPayload;
    }

    /**
     * @return string|null
     */
    public function getCallbackPayload(): ?string
    {
        return $this->callbackPayload;
    }

    /**
     * Check if we finished running everything.
     *
     * @return bool
     */
    public function isCompleted(): bool
    {
        return $this->getCallbackPayload() === null;
    }
}
