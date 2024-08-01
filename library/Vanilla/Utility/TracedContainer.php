<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Utility;

use Garden\Container\Container;

/**
 * Custom container that supports timing things with {@link Timers::class}
 */
class TracedContainer extends Container
{
    /**
     * @var bool Whether or not to trace.
     */
    private static $shouldTrace = false;

    /**
     * Overridden to support timing instantiations.
     *
     * @param $nid
     * @param array $args
     *
     * @return object
     */
    protected function createInstance($nid, array $args)
    {
        if (!self::$shouldTrace) {
            return parent::createInstance($nid, $args);
        }

        $timers = $this->get(Timers::class);

        $span = $timers->startGeneric("create-instance", ["name" => "{$nid}::__construct()"]);
        try {
            return parent::createInstance($nid, $args);
        } finally {
            $span->finish();
        }
    }

    /**
     * Run a callback and time all instantiations inside of it.
     *
     * @param callable $run
     *
     * @return mixed
     */
    public static function trace(callable $run)
    {
        self::$shouldTrace = true;
        try {
            return $run();
        } finally {
            self::$shouldTrace = false;
        }
    }

    /**
     * @param bool $shouldTrace
     */
    public static function setShouldTrace(bool $shouldTrace): void
    {
        self::$shouldTrace = $shouldTrace;
    }
}
