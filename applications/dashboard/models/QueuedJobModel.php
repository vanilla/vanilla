<?php
/**
 * @author Pavel Goncharov <pgoncharov@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
namespace Vanilla\Dashboard\Models;

use Garden\Http\HttpClient;
use Gdn;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\Lock\LockInterface;
use Vanilla\Analytics\TrackableUserModel;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\CurrentTimeStamp;
use Vanilla\Database\Operation\CurrentDateFieldProcessor;
use Vanilla\Database\Operation\JsonFieldProcessor;
use Vanilla\Formatting\DateTimeFormatter;
use Vanilla\Logger;
use Vanilla\Models\LockService;
use Vanilla\Models\PipelineModel;
use Vanilla\Scheduler\DeferredScheduler;
use Vanilla\Scheduler\Descriptor\NormalJobDescriptor;
use Vanilla\Scheduler\Job\JobExecutionStatus;
use Vanilla\Scheduler\SchedulerInterface;
use Vanilla\Utility\DebugUtils;

/**
 * Model for interacting with the queued job table
 */
class QueuedJobModel extends PipelineModel implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public const CONF_THRESHOLD = "JobQueue.Threshold";
    public const CONF_BULK_CHUNK = "JobQueue.BulkChunk";

    private const TABLE_NAME = "queuedJob";
    private const QUEUED_KEY_LOCK = "queued_lock";
    //5 second wait time.
    private const WAIT_TIME = 5;

    /** @var SchedulerInterface */
    private $scheduler;

    /** @var LockService */
    private $lockService;

    /** @var ConfigurationInterface */
    private $config;

    /**  @var \Gdn_Database */
    protected $database;

    // Job Statuses.

    /** Used by jobs that have not been sent to the remote queue yet. */
    public const STATUS_PENDING = "pending";

    /** Used by jobs that have been receieved by the remote queued. */
    public const STATUS_QUEUED = "queued";

    /** Used by jobs while they are executing. */
    public const STATUS_RUNNING = "running";

    /** Used by some jobs that may take multiple iterations to complete. */
    public const STATUS_PROGRESS = "progress";

    /** Used when a job is completed and successful. */
    public const STATUS_SUCCESS = "success";

    /** Used when a job has had an error, but may be attempted again. */
    public const STATUS_ERROR = "error";

    /** The job is complete but had some errors. */
    public const STATUS_FAILED_PARTIAL = "failed_partial";

    /** Used when a job has failed and exhausted all retries. */
    public const STATUS_FAILED = "failed";

    /** Used when a job has failed and exhausted all retries. */
    public const STATUS_ABANDONED = "abandoned";
    const STATUSES = [
        self::STATUS_RUNNING,
        self::STATUS_FAILED_PARTIAL,
        self::STATUS_PROGRESS,
        self::STATUS_SUCCESS,
        self::STATUS_FAILED,
        self::STATUS_ERROR,
        self::STATUS_ABANDONED,
    ];

    // Driver options.
    const DRIVER = "driver";
    const QUEUE_SERVICE = "queue-service";
    const REGISTRATION_OPTIONS = [self::DRIVER, self::QUEUE_SERVICE];

    /**
     *
     * QueuedJobModel constructor.
     * @param ConfigurationInterface $config
     * @param SchedulerInterface $scheduler
     * @param LockService $lockService
     * @param \Gdn_Database $database
     * @param LoggerInterface $logger ,
     */
    public function __construct(
        ConfigurationInterface $config,
        SchedulerInterface $scheduler,
        LockService $lockService,
        \Gdn_Database $database,
        LoggerInterface $logger
    ) {
        parent::__construct(self::TABLE_NAME);
        $this->addPipelineProcessor(new JsonFieldProcessor(["message", "metrics"]));
        $this->lockService = $lockService;
        $this->config = $config;
        $this->scheduler = $scheduler;
        $this->database = $database;
        $this->setLogger($logger);
    }

    /**
     * Get Threshold config.
     *
     * @return int
     */
    public function getJobThreshold(): int
    {
        return $this->config->get(self::CONF_THRESHOLD, 0);
    }

    /**
     * Get BulkChunk config.
     *
     * @return int
     */
    public function getBulkChunk(): int
    {
        return $this->config->get(self::CONF_BULK_CHUNK, 50);
    }

    /**
     * Set Scheduler
     *
     * @param SchedulerInterface $scheduler
     */
    public function setScheduler(SchedulerInterface $scheduler)
    {
        $this->scheduler = $scheduler;
    }

    /**
     * Prune old rows.
     *
     * @param int|null $limit Then number of rows to delete or **null** to use the default prune limit.
     */
    public function prune($limit = null)
    {
        $dateSuccessPrune = new \DateTimeImmutable("-1 month");
        $dateCommonPrune = new \DateTimeImmutable("-3 month");

        $options = [];
        if ($limit === null || $limit === 0) {
            $options["limit"] = 10; //Default prune limit
        } else {
            $options["limit"] = $limit;
        }

        $this->delete(
            [
                "dateUpdated <" => DateTimeFormatter::timeStampToDateTime($dateCommonPrune->getTimestamp()),
            ],
            $options
        );

        $this->delete(
            [
                "dateUpdated <" => DateTimeFormatter::timeStampToDateTime($dateSuccessPrune->getTimestamp()),
                "status" => [self::STATUS_SUCCESS],
            ],
            $options
        );
    }

    /**
     * Structure the profileField table schema.
     *
     * @return void
     * @throws \Exception
     */
    public static function structure()
    {
        Gdn::structure()
            ->table(self::TABLE_NAME)
            ->primaryKey("queuedJobID")
            ->column("jobID", "varchar(255)", false, ["unique.jobID"])
            ->column("jobType", "varchar(255)", false, ["index.jobExecute"])
            ->column("driver", "varchar(50)", false, ["index.jobExecute", "index.scheduling"])
            ->column("message", "mediumtext", true)
            ->column("status", "varchar(100)", false, ["index.scheduling"])
            ->column("metrics", "mediumtext", null)
            ->column("insertUserID", "int")
            ->column("dateInserted", "datetime", false, ["index.scheduling"])
            ->column("updateUserID", "int", null)
            ->column("dateUpdated", "datetime", null)
            ->column("dateQueued", "datetime", null)
            ->column("dateCompleted", "datetime", null)
            ->set();

        // If the previously named `result` field exists, we migrate the values into the newly created `metrics` field and drop the field.
        if (
            Gdn::structure()
                ->table(self::TABLE_NAME)
                ->columnExists("result")
        ) {
            $sql = Gdn::database()->sql();
            $sql->update(self::TABLE_NAME)
                ->set("metrics", "result", false, false)
                ->put();

            Gdn::structure()
                ->table(self::TABLE_NAME)
                ->dropColumn("result");
        }

        Gdn::structure()
            ->table(self::TABLE_NAME)
            ->dropIndexIfExists("IX_queuedJob_prune")
            ->createIndexIfNotExists("IX_queuedJob_dateUpdated_status", ["dateUpdated", "status"]);
    }

    /**
     * Add a queue job.
     *
     * @param array $set Field values to set.
     * @param array $options See Vanilla\Models\Model::OPT_*
     * @return mixed ID of the inserted row.
     * @throws \Exception If an error is encountered while performing the query.
     */
    public function insert(array $set, array $options = [])
    {
        $this->prune();
        if (!isset($set["status"])) {
            $set["status"] = $this::STATUS_PENDING;
        }
        if (!isset($set["driver"])) {
            $set["driver"] = $this::DRIVER;
        }
        if (!isset($set["jobID"])) {
            $set["jobID"] = TrackableUserModel::uuid();
        }
        if (!isset($set["dateInserted"])) {
            $set["dateInserted"] = DateTimeFormatter::getCurrentDateTime();
        }
        if (!isset($set["dateUpdated"])) {
            $set["dateUpdated"] = $set["dateInserted"];
        }
        if (!isset($set["insertUserID"])) {
            $session = \Gdn::getContainer()->get(\Gdn_Session::class);
            $set["insertUserID"] = $session->UserID;
        }

        $result = parent::insert($set);
        return $result;
    }

    /**
     * Check to schedule Job
     * @param string $jobDescriptor Class name of the job to schedule
     *
     * @throws \Exception
     */
    public function checkToScheduleJob(string $jobDescriptor)
    {
        $subquery = $this->createSql();
        $subquery
            ->select($this->getPrimaryKey())
            ->from($this::TABLE_NAME)
            ->where(["status" => $this::STATUS_PENDING, "driver" => $this::QUEUE_SERVICE])
            ->limit($this->getJobThreshold() + 1);
        $pendingSql = $this->createSql();
        $pendingSql->namedParameters($subquery->namedParameters());
        $pendingCount = $pendingSql->getCount("(" . $subquery->getSelect() . ") count ");

        $hasEarlyJob = false;
        $earliestPendingJob = $this->database
            ->createSql()
            ->from($this->getTableName())
            ->where(["status" => $this::STATUS_PENDING, "driver" => $this::QUEUE_SERVICE])
            ->orderBy("dateInserted")
            ->limit(1)
            ->get()
            ->resultArray();
        if (count($earliestPendingJob) > 0) {
            $earliestPending = $earliestPendingJob[0]["dateInserted"];
            $now = CurrentTimeStamp::getCurrentTimeDifference(new \DateTimeImmutable($earliestPending));

            $hasEarlyJob = $now >= 60;
        }

        // Trigger job when we have jobThreshold(default 50) number of jobs queued, or latest queued job was queued 1 minute ago.
        if ($pendingCount >= $this->getJobThreshold() || $hasEarlyJob) {
            $jobDescriptor = new NormalJobDescriptor($jobDescriptor);
            $this->scheduler->addJobDescriptor($jobDescriptor);
        }
    }

    /**
     * Job to load bulk portion of jobs and schedule them.
     *
     * @param HttpClient $client client used to send jobs.
     * @throws \Exception
     */
    public function scheduleQueuedJobs(HttpClient $client)
    {
        $lock = $this->createLock();
        try {
            if ($lock->acquire()) {
                $this->sendJobs($client);
            }
        } catch (\Exception $e) {
            if (DebugUtils::isTestMode()) {
                throw $e;
            }
            $this->logger->warning("Error scheduling queued Jobs .", [
                Logger::FIELD_CHANNEL => Logger::CHANNEL_SYSTEM,
                "event" => "vanilla_queue",
                "exception" => $e,
            ]);
        } finally {
            $lock->release();
        }
    }

    /**
     * Send jobs in batches to the queue service.
     *
     * @param HttpClient $client client used to send jobs.
     * @throws \Exception
     */
    public function sendJobs(HttpClient $client): void
    {
        $maxIterations = 5;

        for ($i = 0; $i < $maxIterations; $i++) {
            $hasMore = $this->sendJobChunks($client);
            if (!$hasMore) {
                break;
            }
        }
    }

    /**
     * Send jobs in batches to the queue service.
     *
     * @param HttpClient $client client used to send jobs.
     *
     * @return bool Has more jobs to send.
     * @throws \Exception
     */
    private function sendJobChunks(HttpClient $client): bool
    {
        // Get a list of jobs to queue.
        $jobsToSchedule = $this->select(
            ["status" => $this::STATUS_PENDING, "driver" => $this::QUEUE_SERVICE],
            [
                self::OPT_ORDER => "dateInserted",
                self::OPT_LIMIT => $this->getBulkChunk(),
                self::OPT_SELECT => ["jobID", "jobType", "message AS jobPayload"],
            ]
        );

        if (count($jobsToSchedule) === 0) {
            return false;
        }

        foreach ($jobsToSchedule as &$job) {
            $job["jobPayload"] = json_decode($job["jobPayload"], false, 512, JSON_THROW_ON_ERROR);
        }

        $updatePayload = [
            "dateQueued" => CurrentTimeStamp::getMySQL(),
            "status" => self::STATUS_QUEUED,
        ];
        $isBlocking = $this->config->get(DeferredScheduler::CONF_USE_BLOCKING_MODE);
        if ($isBlocking) {
            // In blocking mode by the time this response is returned our status has already been updated to it's final status.
            unset($updatePayload["status"]);
        }

        $response = $client->sendJobs($jobsToSchedule);

        $successIDs = [];
        foreach ($response as $jobID => $jobResult) {
            if ($jobResult["success"] ?? false) {
                $successIDs[] = $jobID;
            }
        }

        // Update jobs status for jobs sent to the queue if they were received.
        $this->update($updatePayload, [
            "jobID" => $successIDs,
        ]);

        return count($jobsToSchedule) === $this->getBulkChunk();
    }

    /**
     * Create a lock for running a queued jobs.
     *
     * @return LockInterface
     */
    public function createLock(): LockInterface
    {
        return $this->lockService->createLock(self::QUEUED_KEY_LOCK, 15);
    }
}
