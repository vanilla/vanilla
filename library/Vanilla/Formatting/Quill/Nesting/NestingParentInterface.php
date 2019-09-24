<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting\Quill\Nesting;

use Vanilla\Formatting\Quill\BlotGroup;

/**
 * Interface representing a an item that can contain nestable items.
 */
interface NestingParentInterface {

    /**
     * The group to nest.
     *
     * @param BlotGroup $blotGroup
     *
     * @return void
     * @throws InvalidNestingException If the group can't be nested. Be sure to call ::canNest().
     */
    public function nestGroup(BlotGroup $blotGroup): void;

    /**
     * Determine if a blot group can be nested inside of the parent.
     *
     * @param BlotGroup $blotGroup
     * @return bool
     */
    public function canNest(BlotGroup $blotGroup): bool;
}
