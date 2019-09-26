<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting\Quill\Nesting;

/**
 * Interface for representing items that can be nested.
 */
interface NestableItemInterface {
    /**
     * Get the current nesting depth of the item.
     *
     * @return int
     */
    public function getNestingDepth(): int;
}
