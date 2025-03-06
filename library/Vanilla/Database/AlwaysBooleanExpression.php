<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Database;

/**
 * A where expression that is always true or always false.
 *
 *  Particularly useful if you have a where that is a part of an or expression that you want to optimize/short-circuit.
 */
class AlwaysBooleanExpression extends CallbackWhereExpression
{
    public function __construct(bool $alwaysValue)
    {
        parent::__construct(function (\Gdn_SQLDriver $sql) use ($alwaysValue) {
            if ($alwaysValue) {
                $sql->whereAlwaysTrue();
            } else {
                $sql->whereAlwaysFalse();
            }
        });
    }
}
