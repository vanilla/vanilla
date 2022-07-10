<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Database\Operation;

use Vanilla\Database\Operation;

/**
 * Pipeline class, specifically for database operations.
 */
class Pipeline {

    /** @var callable */
    private $primaryAction;

    /** @var callable */
    private $stack;

    /** @var callable */
    private $postProcessStack;

    /**
     * Database pipeline constructor.
     *
     * @param callable $primaryAction
     */
    public function __construct(callable $primaryAction) {
        $this->primaryAction = $primaryAction;
        $this->stack = function (Operation $operation) {
            return ($this->primaryAction)($operation);
        };

        $this->postProcessStack = function (Operation $operation) {
            return;
        };
    }

    /**
     * Add a processor to the pipeline.
     *
     * @param Processor $processor
     * @return $this
     */
    public function addProcessor(Processor $processor) {
        $stack = $this->stack;
        $this->stack = new class ($processor, $stack) {

            /** @var Processor */
            private $processor;

            /** @var callable */
            private $stack;

            /**
             * Setup the next frame of the stack.
             *
             * @param Processor $processor
             * @param callable $stack
             */
            public function __construct(Processor $processor, callable $stack) {
                $this->processor = $processor;
                $this->stack = $stack;
            }

            /**
             * Execute the stack.
             *
             * @param mixed $value
             * @return mixed
             */
            public function __invoke($value) {
                /**
                 * Passing the stack allows a processor to control whether it will be executed before or after the rest
                 * of the stack, or to avoid processing the rest of the stack, altogether.
                 */
                $result = $this->processor->handle($value, $this->stack);
                return $result;
            }
        };
        return $this;
    }

    /**
     * Add a processor to the pipeline.
     *
     * @param Processor $processor
     * @return $this
     * @deprecated Avoid using post-processors.
     */
    public function addPostProcessor(Processor $processor) {
        $stack = $this->postProcessStack;
        $this->postProcessStack = function ($value) use ($processor, $stack) {
            /**
             * Passing the stack allows a processor to control whether it will be executed before or after the rest of
             * the stack, or to avoid processing the rest of the stack, altogether.
             */
            $result = $processor->handle($value, $stack);
            return $result;
        };
        return $this;
    }

    /**
     * Execute the processing pipeline on a database operation.
     *
     * @param Operation $op Context for the operation to be performed.
     * @param callable|null $primaryAction A closure to perform the database operation.
     * @param bool $executeStack Whether or not to execute the stack with the primary action.
     *
     * @return mixed
     * @deprecated Use processOperation where possible.
     */
    public function process(Operation $op, ?callable $primaryAction = null, bool $executeStack = true) {
        if (!$executeStack) {
            return call_user_func($primaryAction, $op);
        }

        $result = call_user_func($this->stack, $op);
        call_user_func($this->postProcessStack, $op);
        return $result;
    }

    /**
     * Execute the stack on a database operation.
     *
     * @param Operation $op
     * @return mixed
     */
    public function processOperation(Operation $op) {
        $result = ($this->stack)($op);
        return $result;
    }
}
