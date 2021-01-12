<?php
/**
 * @author Eduardo Garcia Julia <eduardo.garciajulia@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Scheduler\Descriptor;

use Throwable;
use Vanilla\Scheduler\Job\JobExecutionType;
use Vanilla\Scheduler\Job\JobPriority;

/**
 * Class NormalJobDescriptor
 */
class NormalJobDescriptor implements JobDescriptorInterface {

    /** @var string */
    protected $jobType;

    /** @var array */
    protected $message;

    /** @var JobPriority */
    protected $priority;

    /** @var int */
    protected $delay;

    /** @var string|null */
    protected $hash = null;

    /**
     * NormalJobDescriptor constructor
     *
     * @param string $jobType
     */
    public function __construct(string $jobType) {
        $this->jobType = $jobType;
        $this->priority = JobPriority::normal();
        $this->delay = 0;
        $this->message = [];
    }

    /**
     * Set message
     *
     * @param array $message
     * @return NormalJobDescriptor
     */
    public function setMessage(array $message): NormalJobDescriptor {
        $this->message = $message;

        return $this;
    }

    /**
     * Set priority
     *
     * @param JobPriority $priority
     * @return NormalJobDescriptor
     */
    public function setPriority(JobPriority $priority): NormalJobDescriptor {
        $this->priority = $priority;

        return $this;
    }

    /**
     * Set delay
     *
     * @param int $delay
     * @return NormalJobDescriptor
     */
    public function setDelay(int $delay): NormalJobDescriptor {
        $this->delay = $delay;

        return $this;
    }

    /**
     * @return JobExecutionType
     */
    public function getExecutionType(): JobExecutionType {
        return JobExecutionType::normal();
    }

    /**
     * @return string
     */
    public function getJobType(): string {
        return $this->jobType;
    }

    /**
     * @return array
     */
    public function getMessage(): array {
        return $this->message;
    }

    /**
     * @return JobPriority
     */
    public function getPriority(): JobPriority {
        return $this->priority;
    }

    /**
     * @return int
     */
    public function getDelay(): int {
        return $this->delay;
    }

    /**
     * GetHash
     *
     * @return string
     */
    public function getHash(): string {
        if ($this->hash === null) {
            try {
                $serialize = serialize($this->getMessage());
            } catch (Throwable $t) {
                $serialize = uniqid('unserializable', true);
            }
            $seed = $this->getJobType().'::'.$serialize;

            $this->hash = sha1($seed).'::'.md5($seed);
        }

        return $this->hash;
    }
}
