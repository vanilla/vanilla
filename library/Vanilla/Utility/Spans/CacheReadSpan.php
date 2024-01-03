<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Utility\Spans;

/**
 * Span for tracking a cache read operation.
 */
class CacheReadSpan extends AbstractSpan
{
    /**
     * @param string|null $parentUUID
     */
    public function __construct(?string $parentUUID)
    {
        parent::__construct("cacheRead", $parentUUID);
    }

    /**
     * Finish the span.
     *
     * @param string[] $cacheKeys
     * @param int $hitCount
     *
     * @return AbstractSpan
     */
    public function finish(array $cacheKeys, int $hitCount): AbstractSpan
    {
        return parent::finishInternal([
            "keys" => $cacheKeys,
            "hitCount" => $hitCount,
        ]);
    }
}
