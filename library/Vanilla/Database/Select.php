<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Database;

/**
 * For use with {@link \Gdn_SQLDriver::select()}
 */
class Select
{
    public string $fieldExpression;

    public string $alias;

    /**
     * Create a raw database select operation.
     *
     * @param string $fieldExpression The raw field expression to pass to the DB driver.
     * @param string $alias The raw field alias to pass to the DB driver.
     */
    public function __construct(string $fieldExpression, string $alias = "")
    {
        $this->fieldExpression = $fieldExpression;
        $this->alias = $alias;
    }
}
