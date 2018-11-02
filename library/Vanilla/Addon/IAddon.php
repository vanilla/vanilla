<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Addon;

/**
 * A simple addon interface.
 */
interface IAddon {
    /**
     * Get the subdirectory of the addon.
     *
     * @return string The addon's subdirectory.
     */
    public function getSubdir(): string;

    /**
     * Get the addon's key.
     *
     * @return string The addon key.
     */
    public function getKey(): string;
}
