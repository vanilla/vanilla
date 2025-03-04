<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Contracts\Models;

/**
 * Models implement this interface to signify they can be vectorized.
 */
interface VectorizeInterface
{
    /**
     * Get the name of the table.
     *
     * @return string
     */
    public function getTableName(): string;
}
