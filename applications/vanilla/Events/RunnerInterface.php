<?php
/**
 * @author Dani Stark <dani.stark@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Community\Events;

/**
 * An interface for Dirty Records event.
 *
 * @package Vanilla\Community\Events
 */
interface RunnerInterface {

    /**
     * Run dirty record job.
     */
    public function run(): void;
}
