<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Database\SetLiterals;

/**
 * Represents a subquery in a set literal.
 */
class SubQuery extends SetLiteral
{
    /**
     * @param \Gdn_SQLDriver $subquery
     */
    public function __construct(private \Gdn_SQLDriver $subquery)
    {
    }

    /**
     * @param \Gdn_SQLDriver $sql
     * @param string $escapedFieldName
     * @return string
     */
    public function toSql(\Gdn_SQLDriver $sql, string $escapedFieldName): string
    {
        return "(" . $this->subquery->getSelect(true) . ")";
    }
}
