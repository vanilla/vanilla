<?php if (!defined('APPLICATION')) exit;

/**
 * test-old-theme Theme Hooks
 *
 * @author    Todd Burry <todd@vanillaforums.com>
 * @copyright 2016 (c) Todd Burry
 * @license   GPLv2
 * @since     1.0.0
 */
class TestOldThemeThemeHooks implements Gdn_IPlugin {
    /**
     * This will run when you "Enable" the theme
     *
     * @since  1.0.0
     * @access public
     * @return bool
     */
    public function setup() {
        return true;
    }
}
