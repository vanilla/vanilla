<?php
/**
 * Plugin interface
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @package Core
 * @since 2.0
 */

/**
 * A simple interface that all plugins must follow.
 *
 * Aside from the Setup method, this is used more to identify plugins than to enforce structure upon them.
 */
interface Gdn_IPlugin {

    /**
     * Run any setup code that a plugin requires before it is ready for general use.
     *
     * This method will be called every time a plugin is enabled,
     * so it should check before performing redundant operations like
     * inserting tables or data into the database. If a plugin has no setup to
     * perform, simply declare this method and return TRUE.
     *
     * Returns a boolean value indicating success.
     *
     * @return boolean
     */
    public function setup();

    /**
     * These methods are invoked if present, but are not required and will be silently ignored
     * if they do not exist.
     */

    // public function onLoad()    - Called as the plugin is instantiated (each page load)
    // public function onDisable() - Called as the plugin is disabled
    // public function cleanUp()   - Called as the plugin is removed
}
