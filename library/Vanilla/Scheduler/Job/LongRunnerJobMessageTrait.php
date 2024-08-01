<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Scheduler\Job;

/**
 * Trait for implemented the message of a long runner job.
 */
trait LongRunnerJobMessageTrait
{
    /** @var string */
    private $class;

    /** @var string */
    private $method;

    /** @var array */
    private $args = [];

    /** @var array */
    private $options = [];

    /**
     * Configure the job using a message.
     *
     * @param array $message
     */
    public function setMessage(array $message)
    {
        $this->class = $message[LongRunnerJob::OPT_CLASS];
        $this->method = $message[LongRunnerJob::OPT_METHOD];
        $this->args = $message[LongRunnerJob::OPT_ARGS];
        $this->options = $message[LongRunnerJob::OPT_OPTIONS];
    }
}
