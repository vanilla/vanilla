<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Fixtures\Database;

use Vanilla\Database\Operation;
use Vanilla\Database\Operation\Processor;

/**
 * Test implementation of ResourceEvent.
 */
class ProcessorFixture implements Processor {

    /** @var callable */
    private $callable;

    /**
     * Constructor.
     *
     * @param callable $callable
     */
    public function __construct(callable $callable) {
        $this->callable = $callable;
    }

    /**
     * @inheritdoc
     */
    public function handle(Operation $operation, callable $stack) {
        $r = call_user_func($this->callable, $operation);
        return $stack($operation);
    }
}
