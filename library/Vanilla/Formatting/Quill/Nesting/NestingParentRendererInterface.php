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
interface NestingParentRendererInterface extends NestingParentInterface {
    /**
     * Render nested groups.
     *
     * @return string HTML of the rendered groups.
     */
    public function renderNestedGroups(): string;

    /**
     * Get all of the nested blot groups.
     *
     * @return BlotGroup[]
     */
    public function getNestedGroups(): array;
}
