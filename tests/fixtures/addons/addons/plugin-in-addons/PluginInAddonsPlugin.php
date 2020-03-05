<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\PluginInAddons;

/**
 * Some theme hooks.
 */
class PluginInAddonsPlugin extends \Gdn_Plugin {
    /**
     * Run migrations.
     */
    public function structure() {
        return true;
    }
}
