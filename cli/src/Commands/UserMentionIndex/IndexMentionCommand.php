<?php
/**
 * @author Olivier Lamy-Canuel <olamy-canuel@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Cli\Commands\UserMentionIndex;

use Gdn;
use Gdn_Database;
use Vanilla\Cli\Utils\DatabaseCommand;
use Vanilla\Cli\Utils\SimpleScriptLogger;
use Vanilla\Dashboard\Models\UserMentionsModel;
use Vanilla\Formatting\Exception\FormattingException;
use Vanilla\Schema\RangeExpression;
use Vanilla\Utility\Timers;

/**
 * Command indexing user mentions.
 */
class IndexMentionCommand extends DatabaseCommand
{
    const maxInt = 2147483647;

    /** @var array */
    private $indexableRecords = [
        "discussion" => [
            "id" => "DiscussionID",
            "parentRecordType" => "category",
            "parentRecordID" => "CategoryID",
            "table" => "Discussion",
            "from" => 1,
            "to" => self::maxInt,
        ],
        "comment" => [
            "id" => "CommentID",
            "parentRecordType" => "discussion",
            "parentRecordID" => "DiscussionID",
            "table" => "Comment",
            "from" => 1,
            "to" => self::maxInt,
        ],
    ];

    /** @var array */
    private $records = ["discussion", "comment"];

    /** @var Gdn_Database */
    private $database;

    /** @var int */
    private $batchSize = 1000;

    /** @var mixed|object|UserMentionsModel */
    private $userMentionsModel;

    /** @var SimpleScriptLogger */
    private $logger;

    /**
     * Set up the required variables.
     */
    public function setup()
    {
        $this->userMentionsModel = Gdn::getContainer()->get(UserMentionsModel::class);
        $this->database = $this->getDatabase();
        $this->logger = new SimpleScriptLogger();
    }

    /**
     * Index the user mentions.
     */
    public function indexMentions()
    {
        $totalStartTime = microtime(true);
        $this->setup();

        foreach ($this->records as $recordType) {
            $offset = 0;
            $record = $this->indexableRecords[$recordType];

            $results = $this->fetchPosts($record, $offset);
            $maxID = $this->getMaxID($recordType);
            $currentMax = $record["from"];

            if (empty($results)) {
                $this->logger->success("There are no {$recordType}s to index.");
                return;
            }

            while ($currentMax < $maxID) {
                $mentions = [];
                $startTime = microtime(true);
                $id = $record["id"];
                $parentRecordType = $record["parentRecordType"];

                foreach ($results as $result) {
                    $body = $result["Body"] ?? null;
                    $format = $result["Format"] ?? null;

                    if ($body === null || $format === null) {
                        // If there is bad data, pass over it and don't let it stop the indexing.
                        continue;
                    }

                    try {
                        $userMentions = $this->userMentionsModel->parseMentions($body, $format);
                    } catch (FormattingException $e) {
                        $this->logger->error("Failed to index mentions from record: {$result[$record["id"]]}", [
                            "recordID" => $result[$record["id"]],
                            "recordType" => $recordType,
                        ]);
                    }

                    foreach ($userMentions as $userMention) {
                        $mentions[] = [
                            "userID" => $userMention["userID"],
                            "mentionedName" => $userMention["name"],
                            "recordType" => $recordType,
                            "recordID" => $result[$id],
                            "parentRecordID" => $result[$record["parentRecordID"]],
                            "parentRecordType" => $parentRecordType,
                            "dateInserted" => $result["DateInserted"],
                        ];
                    }
                }
                $currentMax = $result[$id] ?? $maxID;

                $this->userMentionBulkInsert($mentions);
                $stopTime = microtime(true);
                $time = Timers::formatDuration(($stopTime - $startTime) * 1000);
                $this->logger->success("Last $id indexed: $currentMax/$maxID $time");

                $offset += $this->batchSize;
                $results = $this->fetchPosts($record, $offset);
            }

            $totalStopTime = microtime(true);
            $time = Timers::formatDuration(($totalStopTime - $totalStartTime) * 1000);
            $this->logger->success("Total indexing time: $time");
        }
    }

    /**
     * Fetch the posts based on a recordType and a cursor.
     *
     * @param $record
     * @param $offset
     * @return array|null
     */
    protected function fetchPosts($record, $offset)
    {
        $sql = $this->database->createSql();

        $result = $sql
            ->select([$record["id"], "Body", "Format", "DateInserted", $record["parentRecordID"]])
            ->from($record["table"])
            ->where([$record["id"] => new RangeExpression(">=", $record["from"], "<=", $record["to"])])
            ->offset($offset)
            ->limit($this->batchSize)
            ->get()
            ->resultArray();

        return $result;
    }

    /**
     * Insert user mention records in bulk.
     *
     * @param array $rows
     */
    protected function userMentionBulkInsert(array $rows)
    {
        $sql = $this->database->createSql();
        $sql->options("Ignore", true);
        $sql->insert("userMention", $rows);
    }

    /**
     * A comma-separated list of the records to be indexed (e.g. `discussion,comment`).
     *
     * @param string $records
     */
    public function setRecords(string $records): void
    {
        $records = explode(",", $records);
        $this->records = $records;
    }

    /**
     * Set the size of the batches to be indexed.
     *
     * @param int $batchSize
     */
    public function setBatchSize(int $batchSize): void
    {
        $this->batchSize = $batchSize;
    }

    /**
     * Set the lowest ID that will be processed. Default to 1.
     *
     * @param int $from
     */
    public function setFrom(int $from): void
    {
        $this->indexableRecords["discussion"]["from"] = $from;
        $this->indexableRecords["comment"]["from"] = $from;
    }

    /**
     * Set the highest ID that will be processed.
     *
     * @param int $to
     */
    public function setTo(int $to): void
    {
        $this->indexableRecords["discussion"]["to"] = $to;
        $this->indexableRecords["comment"]["to"] = $to;
    }

    /**
     * Return the highest value between the MaxID of a certain field or the $to argument.
     *
     * @param $recordType
     * @return int
     */
    protected function getMaxID($recordType): int
    {
        $record = $this->indexableRecords[$recordType];
        $sql = $this->database->createSql();
        $id = $record["id"];
        $result = $sql
            ->select($id, "max")
            ->from($record["table"])
            ->get()
            ->firstRow(DATASET_TYPE_ARRAY);

        return min($result[$id], $record["to"]);
    }
}
