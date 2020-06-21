<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Database\SetLiterals;

/**
 * A base class for specifying literal SQL expressions.
 */
abstract class SetLiteral {
    /**
     * Generate the literal SQL expression.
     *
     * @param \Gdn_SQLDriver $sql
     * @param string $escapedFieldName
     * @return string
     */
    abstract public function toSql(\Gdn_SQLDriver $sql, string $escapedFieldName): string;
}
