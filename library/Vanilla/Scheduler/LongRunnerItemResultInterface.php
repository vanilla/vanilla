<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Scheduler;

/**
 * Interface to represent a long runner item result.
 */
interface LongRunnerItemResultInterface
{
    /**
     * Get the record ID of this long runner result.
     *
     * @return int|string
     */
    public function getRecordID();
}
