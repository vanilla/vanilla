<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Contracts;

/**
 * A simple addon interface.
 */
interface AddonInterface {
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
