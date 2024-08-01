<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Utility\Spans;

/**
 * Span for tracking a database operation.
 */
class DbWriteSpan extends AbstractSpan
{
    /**
     * @param string|null $parentUUID
     */
    public function __construct(?string $parentUUID)
    {
        parent::__construct("dbWrite", $parentUUID);
    }

    /**
     * Finish and record the span.
     *
     * @param string $query The database query.
     * @param array $params Parameters applied to a prepared statement.
     *
     * @return DbWriteSpan
     */
    public function finish(string $query, array $params = []): DbWriteSpan
    {
        return parent::finishInternal([
            "query" => $query,
            "params" => $params,
        ]);
    }
}
