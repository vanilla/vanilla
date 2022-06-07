<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Scheduler\Job;

use Garden\Schema\Schema;
use Vanilla\Web\JsonView;
use Vanilla\Web\Middleware\LogTransactionMiddleware;
use Vanilla\Web\Pagination\WebLinking;

/**
 * A local job for handling bulk deletes.
 */
class LocalApiBulkDeleteJob extends LocalApiJob implements TrackableJobAwareInterface
{
    const PROGRESS_EVERY = 10;

    use TrackableJobAwareTrait;

    /** @var JobStatusModel */
    private $jobStatusModel;

    /** @var string */
    private $iteratorUrl;

    /** @var string */
    private $recordIDField;

    /** @var string */
    private $deleteUrlPattern;

    /** @var string|null */
    private $finalDeleteUrl;

    /** @var int|null */
    private $countRecords;

    /** @var array|null */
    private $notify;

    /**
     * DI.
     *
     * @param JobStatusModel $jobStatusModel
     */
    public function __construct(JobStatusModel $jobStatusModel)
    {
        $this->jobStatusModel = $jobStatusModel;
    }

    /**
     * @return JobExecutionStatus
     */
    public function run(): JobExecutionStatus
    {
        $this->vanillaClient->setDefaultHeader(
            LogTransactionMiddleware::HEADER_NAME,
            \LogModel::generateTransactionID()
        );

        foreach ($this->runIterator() as $status) {
            if ($status instanceof JobExecutionProgress) {
                $this->jobStatusModel->progressJob($this, $status);
            } else {
                return $status;
            }
        }

        return JobExecutionStatus::complete();
    }

    /**
     * Get an iterator run executing the job.
     *
     * @param int $progressionFrequency How many items should be deleted before yielding with a new progress status.
     *
     * @return \Generator<JobExecutionStatus>
     * @internal Only exposed publicly for usage in tests.
     */
    public function runIterator(int $progressionFrequency = self::PROGRESS_EVERY): \Generator
    {
        $progressionFrequency = max($progressionFrequency, 1);
        $quantityComplete = 0;
        $nextPage = null;
        do {
            $response = $this->vanillaClient->get($this->iteratorUrl);
            if ($nextPage === null) {
                if ($countHeader = $response->getHeader(JsonView::TOTAL_COUNT_HEADER)) {
                    $this->countRecords = intval($countHeader);
                }
                // first iteration.
            }
            $header = $response->getHeader(WebLinking::HEADER_NAME);
            $linkHeaders = WebLinking::parseLinkHeaders($header);
            $nextPage = $linkHeaders["next"] ?? null;
            $apiResults = $response->getBody();
            // Loop through each one and delete it.
            foreach ($apiResults as $apiResult) {
                $id = $apiResult[$this->recordIDField];
                $deleteUrl = str_replace(":recordID", $id, $this->deleteUrlPattern);
                $this->vanillaClient->delete($deleteUrl);
                $quantityComplete++;
                if ($this->countRecords > 0 && $quantityComplete % $progressionFrequency === 0) {
                    yield new JobExecutionProgress($this->countRecords, $quantityComplete);
                }
            }
        } while ($nextPage !== null);

        if ($this->finalDeleteUrl) {
            $this->vanillaClient->delete($this->finalDeleteUrl);
        }

        yield JobExecutionStatus::complete();
    }

    /**
     * @inheritdoc
     */
    public function setMessage(array $message)
    {
        $schema = Schema::parse([
            "iteratorUrl:s",
            "recordIDField:s",
            "deleteUrlPattern:s", // Pattern "/some/path/:recordID/asdfasdf
            "finalDeleteUrl:s?",
            "countRecords:s?",
            "notify:o?" => ["userID:i", "title:s", "body:s"],
        ]);

        $message = $schema->validate($message);
        $this->iteratorUrl = $message["iteratorUrl"];
        $this->recordIDField = $message["recordIDField"];
        $this->deleteUrlPattern = $message["deleteUrlPattern"];
        $this->countRecords = $message["countRecords"] ?? null;

        if (strpos($this->deleteUrlPattern, ":recordID") === false) {
            throw new \Exception(
                "Unable to queue a bulk delete job with an invalid deleteUrlPattern. It must contain a `:recordID` placeholder"
            );
        }

        $this->finalDeleteUrl = $message["finalDeleteUrl"] ?? null;
        $this->notify = $message["notify"] ?? null;
    }
}
