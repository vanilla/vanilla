<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Database\Operation;

use Vanilla\Database\Operation;

/**
 * Processor of operations in a database pipeline.
 */
interface Processor {

    /**
     * Perform actions based on a database operation.
     *
     * @param Operation $databaseOperation
     * @param callable $stack
     * @return mixed
     */
    public function handle(Operation $databaseOperation, callable $stack);
}
