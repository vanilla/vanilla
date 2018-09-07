<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Garden;

/**
 * Simple data processing pipeline.
 */
class Pipeline {

    /** @var callable */
    private $stack;

    /**
     * Pipeline constructor.
     */
    public function __construct() {
        $this->stack = function ($value) {
            return $value;
        };
    }

    /**
     * Add a processor to the pipeline.
     *
     * @param callable $processor
     * @return $this
     */
    public function addProcessor(callable $processor) {
        $next = $this->stack;
        $this->stack = function ($value) use ($processor, $next) {
            /**
             * Passing the stack allows a processor to control whether it will be executed before or after the rest of
             * the stack, or to avoid processing the rest of the stack, altogether.
             */
            $result = $processor($value, $next);
            return $result;
        };
        return $this;
    }

    /**
     * Execute the processing pipeline on a value.
     *
     * @param mixed $value
     * @return mixed
     */
    public function process($value) {
        $result = call_user_func($this->stack, $value);
        return $result;
    }
}
