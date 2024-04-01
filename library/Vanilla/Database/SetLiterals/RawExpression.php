<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Database\SetLiterals;

use Garden\Web\Exception\ServerException;

/**
 * A data class that represents a raw database expression.
 *
 * Instances of this class are meant to pass to `Gdn_SQLDriver::set()` or to models in order to set a value to a raw database expression.
 * Here are some examples:
 *
 * ```php
 * Gdn::sql()->put('Discussion', ['hot' => new Raw(<<<SQL
 * floor(
 *     unix_timestamp(DateInserted) + unix_timestamp(
 *         date_add(
 *             coalesce(DateLastComment, DateUpdated, DateInserted, '1970-01-01'),
 *                 interval(CountComments * 10) + (
 *                 coalesce(score, 0) * 5
 *             ) minute
 *         )
 *     )
 * )
 * SQL)]);
 * ```
 */
class RawExpression extends SetLiteral
{
    private string $expression;

    /**
     * @param string $expression NEVER put user input into this expression.
     */
    public function __construct(string $expression)
    {
        $this->expression = $expression;
    }

    /**
     * @return string
     */
    public function getExpression(): string
    {
        return $this->expression;
    }

    /**
     * {@inheritDoc}
     */
    public function toSql(\Gdn_SQLDriver $sql, string $escapedFieldName): string
    {
        if (str_contains($this->expression, "drop") || str_contains($this->expression, "truncate")) {
            throw new ServerException("You may not use `drop` or `truncate` in a raw expression");
        }
        return $this->getExpression();
    }
}
