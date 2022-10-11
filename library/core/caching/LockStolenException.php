<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

/**
 * Exception for StealableLock.
 */
class LockStolenException extends Exception
{
    /**
     * Thrown when a stealable lock has been stolen.
     *
     * @param string $message
     * @param Throwable|null $previous
     */
    public function __construct(string $cacheKey, ?Throwable $previous = null)
    {
        parent::__construct("Stealable lock for {$cacheKey} has been stolen by another operation", 423, $previous);
    }
}
