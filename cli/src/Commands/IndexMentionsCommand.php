<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Cli\Commands;

use Gdn;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Vanilla\Cli\Utils\DatabaseCommand;
use Vanilla\Cli\Utils\ScriptLoggerTrait;
use Vanilla\Dashboard\Models\UserMentionsModel;
use Vanilla\Formatting\Exception\FormattingException;
use Vanilla\Schema\RangeExpression;
use Vanilla\Utility\Timers;

/**
 * Command indexing user mentions.
 */
class IndexMentionsCommand extends DatabaseCommand
{
    use ScriptLoggerTrait;

    const maxInt = 2147483647;

    /** @var array */
    private array $indexableRecords = [
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
    private array $records = ["discussion", "comment"];

    /** @var UserMentionsModel */
    private UserMentionsModel $userMentionsModel;

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        parent::configure();
        $this->setName("index-mentions")->setDescription(
            "Command for indexing user mentions in comments and discussions into the GDN_userMention table."
        );
        $definition = $this->getDefinition();
        $definition->addOption(
            new InputOption(
                "records",
                null,
                InputOption::VALUE_REQUIRED,
                "A comma-separated list of the records to be indexed (e.g. `discussion,comment`)."
            )
        );
        $definition->addOption(
            new InputOption("to", null, InputOption::VALUE_REQUIRED, "Set the highest ID that will be processed.")
        );
        $definition->addOption(
            new InputOption(
                "batch-size",
                null,
                InputOption::VALUE_REQUIRED,
                "Set the number of records processed at a time."
            )
        );
        $definition->addOption(
            new InputOption(
                "from",
                null,
                InputOption::VALUE_REQUIRED,
                "Set the lowest ID that will be processed. Default to 1.",
                1
            )
        );
    }

    /**
     * @inheritdoc
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
        $batchSize = $input->getOption("batch-size");
        if ($batchSize !== null) {
            $this->setBatchSize($batchSize);
        }

        $to = $input->getOption("to");
        if ($to !== null) {
            $this->setTo($to);
        }

        $from = $input->getOption("from");
        if ($from !== null) {
            $this->setFrom($from);
        }

        $records = $input->getOption("records");
        if ($records !== null) {
            $this->setRecords($records);
        }
        $this->userMentionsModel = Gdn::getContainer()->get(UserMentionsModel::class);
    }

    /**
     * Index the user mentions.
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $totalStartTime = microtime(true);

        foreach ($this->records as $recordType) {
            $offset = 0;
            $record = $this->indexableRecords[$recordType];
            $currentMax = $record["from"];
            $maxID = $this->getMaxID($record["id"], $record["table"], $record["to"]);
            $results = $this->fetchPosts($record, $offset);

            if (empty($results)) {
                $this->logger()->success("There are no {$recordType}s to index.");
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
                        $this->logger()->error("Failed to index mentions from record: {$result[$record["id"]]}", [
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
                $this->logger()->success("Last $id indexed: $currentMax/$maxID $time");

                $offset += $this->batchSize;
                $results = $this->fetchPosts($record, $offset);
            }

            $totalStopTime = microtime(true);
            $time = Timers::formatDuration(($totalStopTime - $totalStartTime) * 1000);
            $this->logger()->success("Total indexing time: $time");
        }
        return self::SUCCESS;
    }

    /**
     * Insert user mention records in bulk.
     *
     * @param array $rows
     */
    protected function userMentionBulkInsert(array $rows)
    {
        $sql = $this->getDatabase()->createSql();
        $sql->options("Ignore", true);
        $sql->insert("userMention", $rows);
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
}
