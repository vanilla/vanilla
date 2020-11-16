<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Scheduler\Job;

use Exception;

/**
 * Execute a callback as a job.
 */
class CallbackJob implements LocalJobInterface {

    /** @var callable */
    private $callback;

    /** @var array */
    private $parameters = [];

    /**
     * Execute the configured callback function.
     */
    public function run(): JobExecutionStatus {
        call_user_func_array($this->callback, $this->parameters);

        return JobExecutionStatus::complete();
    }

    /**
     * Set job Message
     *
     * @param array $message
     * @throws Exception With an invalid Message configuration.
     */
    public function setMessage(array $message) {
        $callback = $message["callback"] ?? null;
        $parameters = $message["parameters"] ?? [];

        if (is_callable($callback) === false) {
            throw new Exception("Invalid callback configuration for ".__CLASS__);
        }
        $this->callback = $callback;

        if (!is_array($parameters)) {
            throw new Exception("Invalid parameter configuration for ".__CLASS__);
        }
        $this->parameters = $parameters;
    }
}
