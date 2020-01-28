<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\ThemeInAddons;

/**
 * Some theme hooks.
 */
class ThemeInAddonsThemeHooks extends \Gdn_Plugin {
    /**
     * This will run when you "Enable" the theme
     */
    public function setup() {
        return true;
    }
}
