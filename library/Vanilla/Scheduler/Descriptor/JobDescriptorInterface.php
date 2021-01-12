<?php
/**
 * @author Eduardo Garcia Julia <eduardo.garciajulia@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Scheduler\Descriptor;

use Vanilla\Scheduler\Job\JobExecutionType;
use Vanilla\Scheduler\Job\JobPriority;

/**
 * Interface JobDescriptorInterface
 */
interface JobDescriptorInterface {

    /**
     * @return JobExecutionType
     */
    public function getExecutionType(): JobExecutionType;

    /**
     * @return string
     */
    public function getJobType(): string;

    /**
     * @return array
     */
    public function getMessage(): array;

    /**
     * @return JobPriority
     */
    public function getPriority(): JobPriority;

    /**
     * @return int
     */
    public function getDelay(): int;

    /**
     * GetHash
     *
     * @return string
     */
    public function getHash(): string;
}
