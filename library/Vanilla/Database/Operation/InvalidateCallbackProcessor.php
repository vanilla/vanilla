<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Database\Operation;

use Vanilla\Database\Operation;
use Vanilla\Database\Operation\Processor;

/**
 * Processor for doing some invalidation of cache.
 */
class InvalidateCallbackProcessor implements Processor {

    /** @var callable */
    private $callback;

    /**
     * @param callable $callback
     */
    public function __construct(callable $callback) {
        $this->callback = $callback;
    }

    /**
     * Clear the cache on certain operations.
     *
     * @param Operation $operation
     * @param callable $stack
     * @return mixed|void
     */
    public function handle(Operation $operation, callable $stack) {
        $result = $stack($operation);

        if (in_array($operation->getType(), [Operation::TYPE_INSERT, Operation::TYPE_DELETE, Operation::TYPE_UPDATE])) {
            call_user_func($this->callback, $operation);
        }

        return $result;
    }
}
