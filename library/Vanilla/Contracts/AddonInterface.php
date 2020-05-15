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

    /**
     * Get information about the addon.
     *
     * @return array
     */
    public function getInfo(): array;

    /**
     * Get a single value from the info array.
     *
     * @param string $key The key in the info array.
     * @param mixed $default The default value to return if there is no item.
     * @return mixed Returns the info value or {@link $default}.
     */
    public function getInfoValue(string $key, $default = null);

    /**
     * Make a full path from an addon-relative path.
     *
     * @param string $subpath The subpath to base the path on, starting with a "/".
     * @param string $relative One of the **Addon::PATH_*** constants.
     * @return string Returns a full path.
     */
    public function path($subpath = '', $relative = '');
}
