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
     */
    public function __construct() {
        $this->stack = function (Operation $databaseOperation) {
            return call_user_func($this->primaryAction, $databaseOperation);
        };

        $this->postProcessStack = function (Operation $databaseOperation) {
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
        $this->stack = function ($value) use ($processor, $stack) {
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
     * Add a processor to the pipeline.
     *
     * @param Processor $processor
     * @return $this
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
     * @param Operation $databaseOperation Context for the operation to be performed.
     * @param callable $primaryAction A closure to perform the database operation.
     * @param bool $executeStack Whether or not to execute the stack with the primary action.
     *
     * @return mixed
     */
    public function process(Operation $databaseOperation, callable $primaryAction, bool $executeStack = true) {
        if (!$executeStack) {
            return call_user_func($primaryAction, $databaseOperation);
        } else {
            $this->primaryAction = $primaryAction;
            $result = call_user_func($this->stack, $databaseOperation);
            call_user_func($this->postProcessStack, $databaseOperation);
            return $result;
        }
    }
}
