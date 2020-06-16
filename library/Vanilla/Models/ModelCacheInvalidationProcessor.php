<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Models;

use Vanilla\Database\Operation;
use Vanilla\Database\Operation\Processor;

/**
 * Processor for doing some invalidation of cache.
 */
class ModelCacheInvalidationProcessor implements Processor {

    /** @var ModelCache */
    private $cache;

    /**
     * @param ModelCache $cache
     */
    public function __construct(ModelCache $cache) {
        $this->cache = $cache;
    }

    /**
     * Clear the cache on certain operations.
     *
     * @param Operation $databaseOperation
     * @param callable $stack
     * @return mixed|void
     */
    public function handle(Operation $databaseOperation, callable $stack) {
        $result = $stack($databaseOperation);

        if (in_array($databaseOperation->getType(), [Operation::TYPE_INSERT, Operation::TYPE_DELETE, Operation::TYPE_UPDATE])) {
            $this->cache->invalidateAll();
        }

        return $result;
    }
}
