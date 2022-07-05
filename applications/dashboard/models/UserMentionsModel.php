<?php
/**
 * @author Olivier Lamy-Canuel <olamy-canuel@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Dashboard\Models;

use Exception;

use Garden\Web\Exception\NotFoundException;
use Gdn;
use Generator;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use UserModel;
use Vanilla\Formatting\DateTimeFormatter;
use Vanilla\Formatting\FormatService;
use Vanilla\Logger;
use Vanilla\Models\PipelineModel;
use Vanilla\Scheduler\LongRunner;
use Vanilla\Scheduler\LongRunnerFailedID;
use Vanilla\Scheduler\LongRunnerNextArgs;
use Vanilla\Scheduler\LongRunnerSuccessID;
use Vanilla\Scheduler\LongRunnerTimeoutException;
use Vanilla\Utility\ArrayUtils;
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

    /** @var UserModel */
    private $userModel;

    /** @var FormatService */
    private $formatService;

    const DISCUSSION = "discussion";
    const COMMENT = "comment";

    /**
     * Class constructor.
     *
     * @param UserModel $userModel
     * @param FormatService $formatService
     */
    public function __construct(UserModel $userModel, FormatService $formatService)
    {
        parent::__construct("userMention");
        $this->userModel = $userModel;
        $this->formatService = $formatService;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSystemCallableMethods(): array
    {
        return ["indexUserMentions", "anonymizeUserMentions"];
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
        $mentionUsers = [];

        $mentions = $this->formatService->parseAllMentions($body, $format);
        $mentions = array_unique($mentions);

        foreach ($mentions as $mentionName) {
            $user = $this->userModel->getByUsername($mentionName);
            if ($user) {
                $mentionUsers[] = [
                    "userID" => $user->UserID,
                    "name" => $user->Name,
                ];
            }
        }

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
     * Run a user mentions indexing job.
     *
     * @param array $models
     * @return Generator<array, array|LongRunnerNextArgs>
     */
    public function indexUserMentions(array $models): Generator
    {
        $logContext = [
            Logger::FIELD_TAGS => ["user mention indexing"],
            Logger::FIELD_CHANNEL => Logger::CHANNEL_SYSTEM,
            "features" => $models,
        ];
        $this->logger->info("Starting indexing user mentions", $logContext);

        try {
            foreach ($models as &$modelData) {
                $model = Gdn::getContainer()->get($modelData["name"]);
                $id = $model->PrimaryKey;
                $modelData["id"] = $modelData["id"] ?? 0;

                if (!($model instanceof UserMentionsInterface)) {
                    throw new Exception($modelData["name"] . "does not support the UserMentionsInterface");
                }

                try {
                    $results = $model
                        ->getWhere([$id . ">" => $modelData["id"]], $id, "asc", self::BUCKET_SIZE)
                        ->resultArray();

                    while (count($results) > 0) {
                        foreach ($results as $result) {
                            try {
                                $recordLogContext = $logContext + ["recordID" => $result[$id]];
                                $userMentions = $this->parseMentions($result["Body"], $result["Format"]);

                                foreach ($userMentions as $userMention) {
                                    $model->insertUserMentions($userMention, $result);
                                }

                                $modelData["id"] = max($modelData["id"], $result[$id]);
                                $this->logger->info(
                                    "Inserted " . count($userMentions) . " users mentions",
                                    $recordLogContext
                                );
                                yield new LongRunnerSuccessID($result[$id]);
                            } catch (LongRunnerTimeoutException $e) {
                                throw $e;
                            } catch (Exception $e) {
                                $this->logger->error("Failed to index record", $recordLogContext + ["exception" => $e]);
                                $modelData["id"] = max($modelData["id"], $result[$id]);
                                yield new LongRunnerFailedID($result[$id], $e);
                            }
                        }
                        $results = $model
                            ->getWhere([$id . ">" => $modelData["id"]], $id, "asc", self::BUCKET_SIZE)
                            ->resultArray();
                    }
                } catch (LongRunnerTimeoutException $timeoutException) {
                    return new LongRunnerNextArgs([$models]);
                }
            }
        } catch (LongRunnerTimeoutException $timeoutException) {
            return new LongRunnerNextArgs([$models]);
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
        return LongRunner::FINISHED;
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
            ->column("recordType", ["discussion", "comment"], false, ["primary", "index.userMention"])
            ->column("recordID", "int", false, ["primary", "index.userMention"])
            ->column("mentionedName", "varchar(100)", null)
            ->column("parentRecordType", "varchar(20)", null)
            ->column("parentRecordID", "int", null)
            ->column("dateInserted", "datetime", null)
            ->column("status", ["active", "removed", "toDelete"], "active")
            ->set($explicit, $drop);
    }
}
