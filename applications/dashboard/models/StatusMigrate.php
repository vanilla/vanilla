<?php
/**
 *
 * @author Sooraj Francis <sfrancis@higherlogic.com>
 * @copyright 2009-2022 Higher Logic LLC.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Models;

use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Scheduler\LongRunner;
use Vanilla\Scheduler\LongRunnerFailedID;
use Vanilla\Scheduler\LongRunnerNextArgs;
use Vanilla\Scheduler\LongRunnerQuantityTotal;
use Vanilla\Scheduler\LongRunnerSuccessID;
use Vanilla\Scheduler\LongRunnerTimeoutException;
use Vanilla\Web\SystemCallableInterface;

/**
 * Status Migrate Model.
 *
 * Parent class for the discussion status migrate classes .
 */
abstract class StatusMigrate implements SystemCallableInterface
{
    const DISCUSSION_TYPE = "";

    /** @var string */
    protected $table;

    /** @var string */

    protected $primaryKey;

    /** @var int */
    protected $limit;

    /** @var ConfigurationInterface */
    private $config;

    /** @var \Gdn_Database */
    protected $database;

    /**@var ?int */
    protected $batchCount = null;

    /** @var int  */
    private $currentBatchIndex = 1;

    /**
     * Class constructor
     *
     * @param ConfigurationInterface $config
     * @param \Gdn_Database $database
     */
    public function __construct(ConfigurationInterface $config, \Gdn_Database $database)
    {
        $this->config = $config;
        $this->database = $database;
        $this->limit = $this->config->get("Dba.Limit", 1000);
    }

    /**
     * @inheritdoc
     */
    public static function getSystemCallableMethods(): array
    {
        return ["migrateStatusIterator"];
    }

    /**
     * Calculate total batch size to process
     * @return int
     */
    protected function calculateBatchCount(): int
    {
        $totalRecords = $this->getTotalCount();
        if ($totalRecords) {
            $this->batchCount = (int) ceil($totalRecords / $this->limit);
        }
        return $this->batchCount ?? 0;
    }

    /**
     * find the total count of records to be migrated
     * @return int
     */
    protected function getTotalCount(): int
    {
        $where = $this->getDefaultCondition();
        if (!strlen($this->table)) {
            return 0;
        }
        return $this->database
            ->createSql()
            ->where($where)
            ->getCount($this->table);
    }

    /**
     * set the table
     * @param string $table
     * @return void
     */
    protected function configure(): void
    {
        $this->table = "Discussion";
        $this->primaryKey = "DiscussionID";
    }

    /**
     * sets current processing index
     * @param int $currentBatchIndex
     */
    public function setCurrentBatchIndex(int $currentBatchIndex): void
    {
        $this->currentBatchIndex = $currentBatchIndex;
    }

    /**
     * get Current Processing index
     * @return int
     */
    public function getCurrentBatchIndex(): int
    {
        return $this->currentBatchIndex;
    }

    /**
     * set current iteration and batch from the options
     * @param array $options
     * @return void
     */
    protected function process(array $options): void
    {
        $this->batchCount = $options["batchCount"] ?? null;
        if (!empty($options["currentBatchIndex"]) && is_numeric($options["currentBatchIndex"])) {
            $this->setCurrentBatchIndex($options["currentBatchIndex"]);
        }
    }

    /**
     * Get Current state of processing
     * @return array
     */
    protected function getCurrentState(): array
    {
        return [
            "batchCount" => $this->batchCount,
            "currentBatchIndex" => $this->getCurrentBatchIndex(),
        ];
    }

    /**
     * provide default condition for the sql queries
     * @return array
     */
    protected function getDefaultCondition($prefix = ""): array
    {
        if (!empty($prefix) && substr($prefix, -1) !== ".") {
            $prefix .= ".";
        }
        return ["{$prefix}Type" => $this->getDiscussionType(), "{$prefix}statusID" => 0];
    }

    /**
     * get the batch count for processing
     * @return int
     */
    protected function getBatchCount(): int
    {
        if (!empty($this->batchCount)) {
            return $this->batchCount;
        }
        return $this->calculateBatchCount();
    }

    /**
     * Iterator for migrating plugin statuses to unified statuses
     * @param array $options
     * @return \Generator
     */
    public function migrateStatusIterator(array $options = []): \Generator
    {
        $this->configure();
        if (empty($options)) {
            $this->getBatchCount();
        } else {
            $this->process($options);
        }
        try {
            if ($this->batchCount) {
                //Calculate total Batch size
                yield new LongRunnerQuantityTotal($this->batchCount);
                for ($i = $this->getCurrentBatchIndex(); $i <= $this->batchCount; $i++) {
                    foreach ($this->processMigrationIterator($i) as $processedBatch) {
                        $this->setCurrentBatchIndex($processedBatch + 1);
                    }
                    yield new LongRunnerSuccessID($i);
                }
            }
        } catch (LongRunnerTimeoutException $ex) {
            if ($this->getCurrentBatchIndex() >= $this->batchCount) {
                return LongRunner::FINISHED;
            }
            $options = $this->getCurrentState();
            return new LongRunnerNextArgs([$options]);
        } catch (\Exception $e) {
            // Failed to due to error processing batch.
            yield new LongRunnerFailedID($this->getCurrentBatchIndex(), $e);
        }

        return LongRunner::FINISHED;
    }

    /**
     * update status for each Ideation batch
     * @param int $batch
     * @return \Generator
     */
    protected function processMigrationIterator(int $batch): \Generator
    {
        $where = $this->getDefaultCondition();
        $sql = $this->database->createSql();

        $discussionIDs = $sql
            ->select($this->primaryKey)
            ->from($this->table)
            ->where($where)
            ->orderBy($this->primaryKey)
            ->limit($this->limit)
            ->get()
            ->column($this->primaryKey);

        if (count($discussionIDs)) {
            $this->updateRecords($discussionIDs);
        }
        yield $batch;
    }

    abstract function getDiscussionType(): string;
}
