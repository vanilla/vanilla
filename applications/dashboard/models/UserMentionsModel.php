<?php
/**
 * @author Olivier Lamy-Canuel <olamy-canuel@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Dashboard\Models;

use DateTime;
use DateTimeInterface;
use Exception;

use Garden\EventManager;
use Gdn;
use Generator;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use UserModel;
use Vanilla\Dashboard\Events\AfterUserAnonymizeEvent;
use Vanilla\Formatting\DateTimeFormatter;
use Vanilla\Formatting\FormatService;
use Vanilla\Logger;
use Vanilla\Models\PipelineModel;
use Vanilla\Scheduler\LongRunner;
use Vanilla\Scheduler\LongRunnerFailedID;
use Vanilla\Scheduler\LongRunnerNextArgs;
use Vanilla\Scheduler\LongRunnerQuantityTotal;
use Vanilla\Scheduler\LongRunnerSuccessID;
use Vanilla\Scheduler\LongRunnerTimeoutException;
use Vanilla\Scheduler\SchedulerInterface;
use Vanilla\Web\SystemCallableInterface;

/**
 * Handle User Mentions.
 */
class UserMentionsModel extends PipelineModel implements LoggerAwareInterface, SystemCallableInterface
{
    use LoggerAwareTrait;

    const LIMIT = 10;
    const BUCKET_SIZE = 50;
    const ACTIVE_STATUS = "active";

    public const INDEXABLE_RECORDS = [
        "discussion" => ["name" => \DiscussionModel::class],
        "comment" => ["name" => \CommentModel::class],
    ];

    /** @var EventManager */
    private $eventManager;

    /** @var UserModel */
    private $userModel;

    /** @var FormatService */
    private $formatService;

    /** @var SchedulerInterface */
    private $scheduler;

    const DISCUSSION = "discussion";
    const COMMENT = "comment";

    /**
     * Class constructor.
     *
     * @param UserModel $userModel
     * @param FormatService $formatService
     * @param SchedulerInterface $scheduler
     * @param EventManager $eventManager
     */
    public function __construct(
        UserModel $userModel,
        FormatService $formatService,
        SchedulerInterface $scheduler,
        EventManager $eventManager
    ) {
        parent::__construct("userMention");
        $this->userModel = $userModel;
        $this->formatService = $formatService;
        $this->scheduler = $scheduler;
        $this->eventManager = $eventManager;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSystemCallableMethods(): array
    {
        return ["indexUserMentions", "anonymizeUserMentions", "getTotalCount"];
    }

    /**
     * Parse the user mentions from the record body and insert the content into GDN_userMention.
     *
     * @param string $body
     * @param string $format
     * @return array
     */
    public function parseMentions(string $body, string $format): array
    {
        $mentions = $this->formatService->parseAllMentions($body, $format);
        $mentions = array_unique($mentions);

        $mentionUsers = $this->userModel->getUserIDsForUserNames($mentions);

        return $mentionUsers;
    }

    /**
     * Get the userMentions for a specific recordType and ID.
     *
     * @param int $recordID
     * @param string $recordType
     * @param int|false $limit
     * @param int $offset
     * @return array
     */
    public function getByRecordID(int $recordID, string $recordType, $limit = false, int $offset = 0): array
    {
        $where = ["recordID" => $recordID, "recordType" => $recordType];
        $result = $this->select($where, [self::OPT_LIMIT => $limit, self::OPT_OFFSET => $offset]);
        return $result;
    }

    /**
     * Delete the userMentions for a specific recordType and ID.
     *
     * @param int $recordID
     * @param string $recordType
     */
    public function deleteByRecordID(int $recordID, string $recordType)
    {
        $this->delete([
            "recordID" => $recordID,
            "recordType" => $recordType,
        ]);
    }

    /**
     * Get the userMentions for a specific UserID.
     *
     * @param int|array $userID
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getByUser($userID, int $limit = self::LIMIT, int $offset = 0): array
    {
        $result = $this->select(["userID" => $userID], [self::OPT_LIMIT => $limit, self::OPT_OFFSET => $offset]);
        return $result;
    }

    /**
     * Fetch the best date to use for the record.
     *
     * @param array $record
     * @return string|\DateTimeImmutable
     */
    public function getDate(array $record)
    {
        $date =
            $record["dateUpdated"] ??
            ($record["dateInserted"] ??
                ($record["DateUpdated"] ?? ($record["DateInserted"] ?? DateTimeFormatter::getCurrentDateTime())));
        return $date;
    }

    /**
     * Get long runner count of total items to process.
     *
     * @param array<class-string<UserMentionsInterface>> $models
     * @return int
     */
    public function getTotalCount(array $modelClasses): int
    {
        $count = 0;
        for ($i = 0; $i < count($modelClasses); $i++) {
            // Create an iterator of records from that model.
            /** @var \Gdn_Model $model */
            $model = Gdn::getContainer()->get($modelClasses[$i]);

            $estimatedRowCount = $model->getTotalRowCount();
            if ($estimatedRowCount > 5000000) {
                // Over this will be very expensive to calculate.
                $count += $estimatedRowCount;
                continue;
            }
            $subQuery = $this->_getNonIndexedQuery($model, [$model->PrimaryKey . ">" => 0]);
            $result = $subQuery->getCount();
            $count += $result;
        }
        return $count;
    }

    /**
     * Run a user mentions indexing job.
     *
     * @param array<class-string<UserMentionsInterface>> $models
     * @return Generator<array, array|LongRunnerNextArgs>
     */
    public function indexUserMentions(
        array $modelClasses,
        ?int $resumeModelIndex = null,
        ?int $resumeLastRecordID = null
    ): Generator {
        $resumeModelIndex = $resumeModelIndex ?? 0;
        $resumeLastRecordID = $resumeLastRecordID ?? 0;

        // Prepare some logging.
        $logContext = [
            Logger::FIELD_TAGS => ["user mention indexing"],
            Logger::FIELD_CHANNEL => Logger::CHANNEL_SYSTEM,
            "features" => $modelClasses,
        ];

        yield new LongRunnerQuantityTotal([$this, "getTotalCount"], [$modelClasses]);

        $this->logger->info("Starting indexing user mentions", $logContext);
        $countMentionsInserted = 0;
        try {
            for (; $resumeModelIndex < count($modelClasses); $resumeModelIndex++) {
                $modelClass = $modelClasses[$resumeModelIndex];
                /** @var \Gdn_Model $model */
                $model = Gdn::getContainer()->get($modelClass);
                $idField = $model->PrimaryKey;

                if (!($model instanceof UserMentionsInterface)) {
                    throw new Exception($modelClass . " does not support the UserMentionsInterface");
                }

                // Create an iterator of records from that model.
                $results = $this->getNonIndexedIterator(
                    $model,
                    [$idField . ">" => $resumeLastRecordID],
                    $idField,
                    "asc",
                    self::BUCKET_SIZE
                );

                foreach ($results as $result) {
                    $recordID = $result[$idField];
                    $runnerID = $model->getTableName() . "_" . $recordID;

                    // Increment the last record we were working on.
                    $resumeLastRecordID = $recordID;
                    try {
                        $recordLogContext = $logContext + ["model" => $modelClass, "recordID" => $recordID];

                        $body = $result["Body"] ?? null;
                        $format = $result["Format"] ?? null;

                        if ($body === null || $format === null) {
                            // If there is bad data pass over it and don't let it stop the indexing.
                            yield new LongRunnerSuccessID($runnerID);
                            continue;
                        }

                        // Parse mentions out and insert them.
                        $userMentions = $this->parseMentions($result["Body"], $result["Format"]);
                        foreach ($userMentions as $userMention) {
                            $model->insertUserMentions($userMention, $result);
                        }
                        $countMentionsInserted += count($userMentions);

                        // Success!
                        yield new LongRunnerSuccessID($runnerID);
                    } catch (LongRunnerTimeoutException $e) {
                        throw $e;
                    } catch (Exception $e) {
                        $this->logger->error(
                            "Failed to index record $resumeLastRecordID",
                            $recordLogContext + ["exception" => $e]
                        );
                        yield new LongRunnerFailedID($runnerID, $e);
                    }
                }

                // Moving onto the next model.
                $resumeLastRecordID = 0;
            }
        } catch (LongRunnerTimeoutException $timeoutException) {
            return new LongRunnerNextArgs([$modelClasses, $resumeModelIndex, $resumeLastRecordID]);
        } finally {
            $this->logger->debug("Inserted " . $countMentionsInserted . " users mentions", $logContext);
        }
        return LongRunner::FINISHED;
    }

    /**
     * Run a longrunner job to anonymize all of a user's mentions.
     *
     * @param int $id
     * @return Generator<array, array|LongRunnerNextArgs>
     */
    public function anonymizeUserMentions(int $id): Generator
    {
        $logContext = [
            Logger::FIELD_TAGS => ["user mention anonymizing"],
            Logger::FIELD_CHANNEL => Logger::CHANNEL_SECURITY,
            "features" => $id,
        ];
        $this->logger->info("Starting anonymizing user mentions", $logContext);

        try {
            $allUserMentions = $this->select(
                ["userID" => $id, "status" => "active"],
                [self::OPT_LIMIT => self::BUCKET_SIZE]
            );

            $failed = 0;

            while (count($allUserMentions) > $failed) {
                // Get the models we're going to need.
                $modelNames = array_unique(array_column($allUserMentions, "recordType"));
                $models = [];
                foreach ($modelNames as $item) {
                    $models[$item] = Gdn::getContainer()->get(self::INDEXABLE_RECORDS[$item]["name"]);
                }

                // Get the format service.
                $formatService = Gdn::getContainer()->get(FormatService::class);

                foreach ($allUserMentions as $mention) {
                    try {
                        $record = $models[$mention["recordType"]]->getID($mention["recordID"], DATASET_TYPE_ARRAY);

                        $formatter = $formatService->getFormatter($record["Format"]);
                        $anonymizedBody = $formatter->removeUserPII($mention["mentionedName"], $record["Body"]);
                        $record["Body"] = $anonymizedBody;
                        $models[$mention["recordType"]]->update(
                            ["Body" => $record["Body"]],
                            [
                                $models[$mention["recordType"]]->PrimaryKey =>
                                    $record[$models[$mention["recordType"]]->PrimaryKey],
                            ]
                        );
                        $this->update(["status" => "removed"], $mention);
                        yield new LongRunnerSuccessID("{$mention["recordType"]}_{$mention["recordID"]}");
                    } catch (LongRunnerTimeoutException $e) {
                        throw $e;
                    } catch (Exception $e) {
                        $failed++;
                        yield new LongRunnerFailedID("{$mention["recordType"]}_{$mention["recordID"]}", $e);
                    }
                }
                $allUserMentions = $this->select(
                    ["userID" => $id, "status" => "active"],
                    [self::OPT_LIMIT => self::BUCKET_SIZE]
                );
            }
        } catch (LongRunnerTimeoutException $exception) {
            return new LongRunnerNextArgs([$id]);
        }
        $userModel = Gdn::getContainer()->get(UserModel::class);
        $user = $userModel->getID($id, DATASET_TYPE_ARRAY);
        $dateTime = new DateTime($user["DateInserted"]);
        $afterUserAnonymizeEvent = new AfterUserAnonymizeEvent($id, $dateTime);
        $this->eventManager->dispatch($afterUserAnonymizeEvent);

        return LongRunner::FINISHED;
    }

    /**
     * Return a query to exclude mentions that were already process.
     *
     * @param \Gdn_Model $model
     * @param array $where
     * @param int $batchSize
     * @return \Gdn_SQLDriver
     */
    protected function _getNonIndexedQuery(\Gdn_Model $model, array $where = [], int $batchSize = 100): \Gdn_SQLDriver
    {
        $type = strtolower($model->getTableName());
        $on = "recordType = \"$type\" and recordID  = $model->PrimaryKey";
        // Make sure we only fetch records that haven't been indexed.
        $where["recordID"] = null;

        $subQuery = $this->createSql()
            ->from($model->getTableName())
            ->join($this->getTableName(), $on, "left")
            ->where($where)
            ->limit($batchSize);
        return $subQuery;
    }

    /**
     * Iterator to fetch non-indexed records of a certain type in batches.
     *
     * @param \Gdn_Model $model
     * @param array $where
     * @param string $orderFields
     * @param string $orderDirection
     * @param int $batchSize
     * @return Generator<int, array>
     */
    public function getNonIndexedIterator(
        \Gdn_Model $model,
        array $where = [],
        string $orderFields = "",
        string $orderDirection = "",
        int $batchSize = 100
    ): Generator {
        $offset = 0;
        while (true) {
            $query = $this->_getNonIndexedQuery($model, $where);

            $query->limit($batchSize, $offset);

            if ($orderFields != "") {
                $query->orderBy($orderFields, $orderDirection);
            }

            $results = $query->get()->resultArray();
            foreach ($results as $result) {
                $primaryKey = $result[$model->PrimaryKey];
                yield $primaryKey => $result;
            }

            $offset += $batchSize;

            if (count($results) < $batchSize) {
                // We made it to the end.
                return;
            }
        }
    }

    /**
     * Structure the userMention table schema.
     *
     * @param \Gdn_Database $database Database handle
     * @param bool $explicit Optional, true to remove any columns that are not specified here,
     * false to retain those columns. Default false.
     * @param bool $drop Optional, true to drop table if it already exists,
     * false to retain table if it already exists. Default false.
     */
    public static function structure(\Gdn_Database $database, bool $explicit = false, bool $drop = false): void
    {
        $database
            ->structure()
            ->table("userMention")
            ->column("userID", "int", false, ["primary", "index.userMention"])
            ->column("recordType", ["discussion", "comment"], false, ["primary", "index.userMention", "index.record"])
            ->column("recordID", "int", false, ["primary", "index.userMention", "index.record"])
            ->column("mentionedName", "varchar(100)", null)
            ->column("parentRecordType", "varchar(20)", null)
            ->column("parentRecordID", "int", null)
            ->column("dateInserted", "datetime", null)
            ->column("status", ["active", "removed", "toDelete"], "active")
            ->set($explicit, $drop);
    }
}
