<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Utility\Spans;

/**
 * Span for tracking a database read operation.
 */
class DbReadSpan extends AbstractSpan
{
    public function __construct(?string $parentUUID)
    {
        parent::__construct("dbRead", $parentUUID);
    }

    public function finish(string $query, array $params = []): AbstractSpan
    {
        return parent::finishInternal([
            "query" => $query,
            "params" => $params,
        ]);
    }
}
