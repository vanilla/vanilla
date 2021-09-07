<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Scheduler\Job;

/**
 * Job status for progress.
 */
class JobExecutionProgress extends JobExecutionStatus {

    /** @var int */
    private $quantityTotal;

    /** @var int */
    private $quantityComplete;

    /** @var int */
    private $quantityFailed;

    /**
     * Constructor.
     *
     * @param int $quantityTotal
     * @param int $quantityComplete
     * @param int $quantityFailed
     */
    public function __construct(int $quantityTotal, int $quantityComplete = 0, int $quantityFailed = 0) {
        $this->quantityTotal = $quantityTotal;
        $this->quantityComplete = $quantityComplete;
        $this->quantityFailed = $quantityFailed;
        parent::__construct('progress');
    }

    /**
     * @return int
     */
    public function getQuantityTotal(): int {
        return $this->quantityTotal;
    }

    /**
     * @return int
     */
    public function getQuantityComplete(): int {
        return $this->quantityComplete;
    }

    /**
     * @return int
     */
    public function getQuantityFailed(): int {
        return $this->quantityFailed;
    }
}
