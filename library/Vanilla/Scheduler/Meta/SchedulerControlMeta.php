<?php
/**
 * @author Eduardo Garcia Julia <eduardo.garciajulia@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Scheduler\Meta;

/**
 * Class SchedulerControlMeta
 */
class SchedulerControlMeta {

    /**
     * @var int
     * Minimum amount of time between to consecutive cron run
     */
    protected $lockTime;

    /** @var string */
    protected $hostname;

    /**
     * SchedulerControlMeta constructor
     *
     * @param int $lockTime
     * @param string $hostname
     */
    public function __construct(int $lockTime = 0, string $hostname = null) {
        $this->lockTime = $lockTime === 0 ? time() : $lockTime;
        $this->hostname = $hostname ?? (gethostname() ?: 'unknown');
    }

    /**
     * @return int
     */
    public function getLockTime(): int {
        return $this->lockTime;
    }

    /**
     * @return string
     */
    public function getHostname(): string {
        return $this->hostname;
    }
}
