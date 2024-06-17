<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Database;

/**
 * Use this in a where expression to perform pass a complicated where expression through a where array call.
 */
class CallbackWhereExpression
{
    /** @var callable(\Gdn_SQLDriver $sql): void */
    public $callback;

    /**
     * @param callable(\Gdn_SQLDriver $sql): void $callback
     */
    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }
}
