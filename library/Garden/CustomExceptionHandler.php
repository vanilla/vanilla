<?php
/**
 * @author Alexander Kim <alexander.k@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Garden;

use Garden\Web\Data;

/**
 * This interface provide a way to bypass default Dispatcher exception handling.
 *
 */
interface CustomExceptionHandler {
    /**
     * Detect if class has custom exception handler for particular throwable exception.
     *
     * @param \Throwable $e
     * @return bool
     */
    public function hasExceptionHandler(\Throwable $e): bool;

    /**
     * Exception handler method
     *
     * @param \Throwable $e
     * @return Data Returns Garden\Web\Data object
     */
    public function handleException(\Throwable $e): Data;
}
