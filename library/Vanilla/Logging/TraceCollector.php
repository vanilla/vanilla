<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Logging;

/**
 * Collector for debug traces.
 */
class TraceCollector
{
    /** @var array */
    private $traces = [];

    /**
     * Trace some information for debugging.
     *
     * @param mixed $value One of the following:
     * - string: A trace message.
     * - other: A variable to output.
     * @param string $type One of the `TRACE_*` constants or a string label for the trace.
     */
    public function addTrace($value, string $type = TRACE_INFO)
    {
        $this->traces[] = [$value, $type];
    }

    /**
     * @return array
     */
    public function getTraces(): array
    {
        return $this->traces;
    }
}
