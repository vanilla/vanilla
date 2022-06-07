<?php
/**
 * @author Sooraj Francis <sfrancis@higherlogic.com>
 * @copyright 2009-2022 Higher Logic LLC.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Models;

use DBAModel;
use Exception;
use Gdn_Database;
use Gdn;
use Generator;
use Vanilla\Web\SystemCallableInterface;
use Vanilla\Scheduler\LongRunner;
use Vanilla\Scheduler\LongRunnerFailedID;
use Vanilla\Scheduler\LongRunnerItemResultInterface;
use Vanilla\Scheduler\LongRunnerNextArgs;
use Vanilla\Scheduler\LongRunnerQuantityTotal;
use Vanilla\Scheduler\LongRunnerSuccessID;
use Vanilla\Scheduler\LongRunnerTimeoutException;

/**
 * Model for merging discussions together.
 */
class AggregateCountModel implements SystemCallableInterface
{
    /** @var int limit */
    protected $limit;
    /** @var object DiscussionModel */
    private $dbaModel;
    /** @var Gdn_Database */
    private $database;

    /* @Note: Initial draft of changes , needs further iterations */

    /**
     * Class constructor
     * @param DBAModel $dbaModel
     * @param Gdn_Database $database
     */
    public function __construct(DBAModel $dbaModel, Gdn_Database $database)
    {
        $this->dbaModel = $dbaModel;
        $this->database = $database;
        $this->limit = Gdn::config("Dba.Limit") ? Gdn::config("Dba.Limit") : 1000;
    }

    /**
     * @inheritdoc
     */
    public static function getSystemCallableMethods(): array
    {
        return ["processAggregatesIterator"];
    }

    /**
     * Generator for recalculating table Columns
     * @return Generator<int, LongRunnerItemResultInterface, string, string|LongRunnerNextArgs>
     * @var array $options
     * @var int $batch
     */
    public function processAggregatesIterator(int $batch, array $options): Generator
    {
        $nextBatch = $batch;
        foreach ($options as $key => $option) {
            try {
                $options[$key]["processedColumns"] = $option["processedColumns"] ?? [];
                if (empty($option["totalCount"])) {
                    $totalCount = $this->calculateTotalCount($option);
                    $options[$key]["totalCount"] = $totalCount;
                } else {
                    $totalCount = $option["totalCount"];
                }
                $batchSize = $totalCount > $this->limit ? (int) ceil($totalCount / $this->limit) : 1;
                yield new LongRunnerQuantityTotal($batchSize);
                try {
                    //loop through each batch and update the counts
                    for ($i = $batch; $i <= $batchSize; $i++) {
                        foreach ($this->processCountIterator($i, $option) as $discussionColumnsYielded) {
                            $options[$key]["processedColumns"][] = $discussionColumnsYielded;
                        }
                        $nextBatch += 1;
                        yield new LongRunnerSuccessID($i);
                    }
                } catch (LongRunnerTimeoutException $ex) {
                    //We need to capture if the timeout happened in middle of an update then we should continue where left off
                    if ($nextBatch > $batch) {
                        $options[$key]["processedColumns"] = [];
                    }
                    if ($nextBatch > $batchSize) {
                        unset($options[$key]);
                        $nextBatch = 1;
                    }

                    return new LongRunnerNextArgs([$nextBatch, $options]);
                } catch (Exception $ex) {
                    yield new LongRunnerFailedID($nextBatch, $ex);

                    return new LongRunnerNextArgs([$nextBatch, $options]);
                }
            } catch (LongRunnerTimeoutException $ex) {
                return new LongRunnerNextArgs([$nextBatch, $options]);
            }
            unset($options[$key]);
            $nextBatch = 1;
        }
        $options = [];
        return LongRunner::FINISHED;
    }

    /**
     * Generator for processing functional columns of a table, which can be a long-running process.
     *
     * User with LongRunner::run* methods.
     * @param int $batchId The batchId to process.
     * @param array $options
     * @return Generator<int, LongRunnerItemResultInterface, string, string|LongRunnerNextArgs>
     */
    public function processCountIterator(int $batchId, array $options): Generator
    {
        $offset = ($batchId - 1) * $this->limit;
        //get the first set of discussionIds to be updated
        $sql = $this->database->createSql();
        $primaryIds = $sql
            ->select($options["primaryField"])
            ->from($options["table"])
            ->orderBy($options["primaryField"])
            ->limit($this->limit, $offset)
            ->get()
            ->column($options["primaryField"]);

        $where[$options["primaryField"]] = $primaryIds;
        $updateColumns = array_diff($options["columns"], $options["processedColumns"]);
        $from = $to = $max = false;
        foreach ($updateColumns as $updateColumn) {
            //We have to set from and to values for updating LastCommentUserID field
            if ($options["table"] == "Discussion" && $updateColumn == "LastCommentUserID") {
                $from = $primaryIds[0];
                $to = $max = end($primaryIds);
            }
            $result = $this->counts($options["table"], $updateColumn, $from, $to, $max, $where);
            if (!$result["Complete"]) {
                trigger_error("Discussion count update failed for $updateColumn.", E_USER_WARNING);
            }
            yield $updateColumn;
        }
    }

    /**
     * Update the counters.
     *
     * @param $table
     * @param $column
     * @param bool $from
     * @param bool $to
     * @param array $where
     * @return mixed
     * @throws \Gdn_UserException
     */
    public function counts($table, $column, $from = false, $to = false, $where = [])
    {
        $model = $this->dbaModel->createModel($table);

        if (!method_exists($model, "Counts")) {
            throw new \Gdn_UserException("The $table model does not support count recalculation.");
        }
        $result = $model->counts($column, $from, $to, $where);

        return $result;
    }

    /**
     * calculate total counts for the table
     * @param array $option
     * @return int $totalCount
     */
    private function calculateTotalCount(array $option = []): int
    {
        $totalCount = 0;
        if (!empty($option)) {
            $totalCount = $this->database
                ->createSql()
                ->select($option["primaryField"], "count", $option["alias"])
                ->get($option["table"])
                ->firstRow()->{$option["alias"]};
        }
        return $totalCount;
    }
}
