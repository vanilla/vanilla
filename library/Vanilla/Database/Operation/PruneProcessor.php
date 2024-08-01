<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Database\Operation;

use Vanilla\Database\Operation;
use Vanilla\PrunableTrait;

/**
 * A processor that deletes old rows with each insert in order to keep a table size smaller.
 */
class PruneProcessor implements Processor
{
    use PrunableTrait {
        prune as protected;
    }

    /**
     * @var Operation
     */
    private $operation;

    /**
     * PruneProcessor constructor.
     *
     * @param string $field The name of the date field to use to filter the prune.
     * @param string $pruneAfter A `strtotime()` expression to filter which records to prune.
     * @param int $limit The number of rows to delete with each prune.
     * @param array $where Extra where clauses for pruning.
     */
    public function __construct(string $field, string $pruneAfter = "30 days", int $limit = 10, array $where = [])
    {
        $this->setPruneField($field);
        $this->setPruneAfter($pruneAfter);
        $this->setPruneLimit($limit);
        $this->setPruneWhere($where);
    }

    /**
     * {@inheritdoc}
     */
    public function handle(Operation $operation, callable $stack)
    {
        $result = $stack($operation);

        $this->operation = $operation;
        if ($operation->getType() === Operation::TYPE_INSERT) {
            $this->prune();
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($where = [], $options = [])
    {
        $this->operation->getCaller()->delete($where, $options);
    }
}
