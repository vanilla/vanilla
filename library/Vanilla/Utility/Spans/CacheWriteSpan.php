<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Utility\Spans;

/**
 * Span for tracking a cache write operation.
 */
class CacheWriteSpan extends AbstractSpan
{
    public function __construct(?string $parentUUID)
    {
        parent::__construct("cacheWrite", $parentUUID);
    }

    /**
     * @param string[] $cacheKeys
     *
     * @return AbstractSpan
     */
    public function finish(array $cacheKeys): AbstractSpan
    {
        return parent::finishInternal([
            "keys" => $cacheKeys,
        ]);
    }
}
