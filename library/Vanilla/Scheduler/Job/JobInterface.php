<?php
/**
 * @author Eduardo Garcia Julia <eduardo.garciajulia@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Scheduler\Job;

/**
 * Queue job interface.
 *
 * Interface for a runnable job payload.
 */
interface JobInterface {

    /**
     * Set job Message
     *
     * @param array $message
     */
    public function setMessage(array $message);
}
