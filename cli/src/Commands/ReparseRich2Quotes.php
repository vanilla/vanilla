<?php
/**
 * @author Olivier Lamy-Canuel <olamy-canuel@higherlogic.com>
 * @copyright 2009-2024 Higher Logic Inc.
 * @license Proprietary
 */

namespace Vanilla\Cli\Commands;

use Exception;
use Gdn;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Vanilla\Cli\Utils\DatabaseCommand;
use Vanilla\Cli\Utils\ScriptLoggerTrait;
use Vanilla\Formatting\Formats\Rich2Format;
use Vanilla\Utility\Timers;

// Remove the memory limit for absurdly large posts.
ini_set("memory_limit", "-1");

/**
 * Loop through every post to reparse the quotes. This is especially useful to strip out nested quotes.
 */
class ReparseRich2Quotes extends DatabaseCommand
{
    use ScriptLoggerTrait;

    const maxInt = 2147483647;

    /** @var array */
    protected array $recordTypes = [
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
    protected array $records = ["discussion", "comment"];

    protected Rich2Format $format;

    /**
     * @inheritdoc
     */
    protected function configure(): void
    {
        parent::configure();
        $this->setName("strip-nested-quotes")->setDescription("Strip nested quotes from rich2 posts.");
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

        $this->format = Gdn::getContainer()->get(Rich2Format::class);
    }
    /**
     * Go through the records and reparse the quotes.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $totalStartTime = microtime(true);

        foreach ($this->records as $recordType) {
            $offset = 0;
            $record = $this->recordTypes[$recordType];
            $currentMax = $record["from"];
            $maxID = $this->getMaxID($record["id"], $record["table"], $record["to"], ["Format" => "Rich2"]);
            $results = $this->fetchPosts($record, $offset, ["Format" => "Rich2"]);

            if (empty($results)) {
                $this->logger()->success("There are no Rich2 {$recordType}s to reparse.");
                return self::SUCCESS;
            }

            while ($currentMax < $maxID) {
                $startTime = microtime(true);
                $id = $record["id"];

                foreach ($results as $result) {
                    try {
                        $body = $this->format->reparseQuotes($result["Body"]);
                        $this->updateBody($result[$id], $record["table"], $body);
                    } catch (Exception $e) {
                        $this->logger()->error("Error re-parsing {$recordType} {$result[$id]}: {$e->getMessage()}");
                    }
                }
                $currentMax = $result[$id] ?? $maxID;

                $stopTime = microtime(true);
                $time = Timers::formatDuration(($stopTime - $startTime) * 1000);
                $this->logger()->success("Last $id updated: $currentMax/$maxID $time");

                $offset += $this->batchSize;
                $results = $this->fetchPosts($record, $offset);
            }

            $totalStopTime = microtime(true);
            $time = Timers::formatDuration(($totalStopTime - $totalStartTime) * 1000);
            $this->logger()->success("Total re-parsing time: $time");
        }
        return self::SUCCESS;
    }

    /**
     * Set the lowest ID that will be processed. Default to 1.
     *
     * @param int $from
     */
    public function setFrom(int $from): void
    {
        $this->recordTypes["discussion"]["from"] = $from;
        $this->recordTypes["comment"]["from"] = $from;
    }

    /**
     * Set the highest ID that will be processed.
     *
     * @param int $to
     */
    public function setTo(int $to): void
    {
        $this->recordTypes["discussion"]["to"] = $to;
        $this->recordTypes["comment"]["to"] = $to;
    }

    /**
     * Update the body of a post.
     *
     * @param $recordID
     * @param $recordType
     * @param $body
     * @return void
     * @throws Exception
     */
    protected function updateBody($recordID, $recordType, $body): void
    {
        $sql = $this->getDatabase()->sql();
        $sql->update($recordType)
            ->set("Body", $body)
            ->where("{$recordType}ID", $recordID)
            ->put();
    }
}
