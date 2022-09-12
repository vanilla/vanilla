<?php
/**
 * @author Sooraj Francis <sfrancis@higherlogic.com>
 * @copyright 2009-2022 Higher Logic LLC.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Models;

use Exception;
use Gdn_Database;
use Gdn;
use Generator;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Web\SystemCallableInterface;
use Vanilla\Scheduler\LongRunner;
use Vanilla\Scheduler\LongRunnerFailedID;
use Vanilla\Scheduler\LongRunnerItemResultInterface;
use Vanilla\Scheduler\LongRunnerNextArgs;
use Vanilla\Scheduler\LongRunnerQuantityTotal;
use Vanilla\Scheduler\LongRunnerSuccessID;
use Vanilla\Scheduler\LongRunnerTimeoutException;

/**
 * Model calculating aggregate counts and fields.
 */
class AggregateCountModel implements SystemCallableInterface
{
    /** @var Gdn_Database */
    private $database;

    /** @var ConfigurationInterface */
    private $config;

    /**
     * DI.
     *
     * @param Gdn_Database $database
     * @param ConfigurationInterface $config
     */
    public function __construct(Gdn_Database $database, ConfigurationInterface $config)
    {
        $this->database = $database;
        $this->config = $config;
    }

    /**
     * @inheritdoc
     */
    public static function getSystemCallableMethods(): array
    {
        return ["processAggregateOption", "getTotalCount"];
    }

    /**
     * Get long runner count of total items to process.
     *
     * @var AggregateCountOption|array $options
     * @return int
     */
    public function getTotalCount($option, ?int $limit = null): int
    {
        if (is_array($option)) {
            $option = AggregateCountOption::fromJson($option);
        }
        if ($option->getCountBatches() === null) {
            $this->calculateBatches($option, $limit);
        }
        return $option->getCountBatches();
    }

    /**
     * Generator for recalculating table Columns.
     *
     * @var AggregateCountOption|array $options
     *
     * @return Generator<int, LongRunnerItemResultInterface, string, string|LongRunnerNextArgs>
     */
    public function processAggregateOption($option, ?int $limit = null): Generator
    {
        $limit = $limit ?? $this->config->get("Dba.Limit", 1000);
        if (is_array($option)) {
            $option = AggregateCountOption::fromJson($option);
        }
        try {
            if ($option->getCountBatches() === null) {
                $this->calculateBatches($option, $limit);
            }

            yield new LongRunnerQuantityTotal([$this, "getTotalCount"], [$option, $limit]);

            /** @var AggregateCountableInterface $model */
            $model = Gdn::getContainer()->get($option->getModelClass());
            //loop through each batch and update the counts
            for ($i = $option->getCurrentBatchIndex(); $i < $option->getCountBatches(); $i++) {
                try {
                    $option->setCurrentBatchIndex($i);
                    [$min, $max] = $this->getIDRanges($option, $limit);
                    for ($j = $option->getCurrentAggregateIndex(); $j < count($option->getAggregates()); $j++) {
                        $option->setCurrentAggregateIndex($j);
                        $aggregate = $option->getAggregates()[$j];
                        $model->calculateAggregates($aggregate, $min, $max);
                        yield;
                    }
                    // Finished iterating, set the aggregate index back to 0.
                    $option->setCurrentAggregateIndex(0);
                    yield new LongRunnerSuccessID($option->getIterationName());
                } catch (LongRunnerTimeoutException $ex) {
                    throw $ex;
                } catch (Exception $ex) {
                    yield new LongRunnerFailedID($option->getIterationName(), $ex);
                }
            }
        } catch (LongRunnerTimeoutException $ex) {
            return new LongRunnerNextArgs([$option, $limit]);
        }
        return LongRunner::FINISHED;
    }

    /**
     * Get a range of IDs for the current batch.
     *
     * @param AggregateCountOption $option
     * @param int $limit
     *
     * @return array
     */
    private function getIDRanges(AggregateCountOption $option, int $limit): array
    {
        $sql = $this->database->createSql();
        $primaryIDs = $sql
            ->select($option->getPrimaryField())
            ->from($option->getTable())
            ->orderBy($option->getPrimaryField(), "desc")
            ->limit($limit, $option->getCurrentBatchIndex() * $limit)
            ->get()
            ->column($option->getPrimaryField());

        $min = min($primaryIDs);
        $max = max($primaryIDs);
        return [$min, $max];
    }

    /**
     * Calculate the total amount of batches on an option.
     *
     * @param AggregateCountOption $option
     * @param int|null $limit
     */
    private function calculateBatches(AggregateCountOption $option, ?int $limit = null)
    {
        $totalCount = $this->database->getEstimatedRowCount($option->getTable());
        if ($totalCount < 5000000) {
            // It shouldn't be too expensive to count this table.
            $totalCount = $this->database->createSql()->getCount($option->getTable());
        }
        $limit = $limit ?? $this->config->get("Dba.Limit", 1000);
        $countBatches = ceil($totalCount / $limit);
        $option->setCountBatches($countBatches);
    }
}
