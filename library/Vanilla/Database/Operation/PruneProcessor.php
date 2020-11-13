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
class PruneProcessor implements Processor {
    use PrunableTrait {
        prune as protected;
        delete as protected;
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
     */
    public function __construct(string $field, string $pruneAfter = '30 days', int $limit = 10) {
        $this->setPruneField($field);
        $this->setPruneAfter($pruneAfter);
        $this->setPruneLimit($limit);
    }

    /**
     * {@inheritdoc}
     */
    public function handle(Operation $operation, callable $stack) {
        switch ($operation->getType()) {
            case Operation::TYPE_INSERT:
                $op = $this->operation;
                try {
                    $this->operation = $operation;
                    $this->prune();
                } finally {
                    $this->operation = $op;
                }
                break;
        }

        return $stack($operation);
    }

    /**
     * {@inheritdoc}
     */
    public function delete($where = [], $options = []) {
        $this->operation->getCaller()->delete($where, $options);
    }
}
