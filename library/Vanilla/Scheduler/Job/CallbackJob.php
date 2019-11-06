<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Scheduler\Job;

use Vanilla\Scheduler\Job\JobExecutionStatus;
use Vanilla\Scheduler\Job\JobPriority;

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
        try {
            call_user_func_array($this->callback, $this->parameters);
        } catch (\Exception $e) {
            return JobExecutionStatus::error();
        }
        return JobExecutionStatus::complete();
    }

    /**
     * Set job Message
     *
     * @param array $message
     */
    public function setMessage(array $message) {
        $callback = $message["callback"] ?? null;
        $parameters = $message["parameters"] ?? [];

        if (is_callable($callback) === false) {
            throw new \Exception("Invalid callback configuration for " . __CLASS__);
        }
        $this->callback = $callback;

        if (!is_array($parameters)) {
            throw new \Exception("Invalid parameter configuration for " . __CLASS__);
        }
        $this->parameters = $parameters;
    }

    /**
     * Set job priority
     *
     * @param JobPriority $priority
     * @return void
     */
    public function setPriority(JobPriority $priority) {
    }

    /**
     * Set job execution delay
     *
     * @param int $seconds
     * @return void
     */
    public function setDelay(int $seconds) {
    }
}
