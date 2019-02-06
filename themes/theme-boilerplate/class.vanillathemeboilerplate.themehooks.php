<?php
/**
 * @author Isis Graziatto <isis.g@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

if (!defined('APPLICATION')) {
    exit();
}

/**
 * Class VanillaThemeBoilerplateThemeHooks
 */
class VanillaThemeBoilerplateThemeHooks extends Gdn_Plugin {

    /**
     * Run once on enable.
     *
     * @return void
     */
    public function setup() {
        $this->structure();
    }

    /**
     * Run on utility/update.
     *
     * @return void
     */
    public function structure() {
        saveToConfig([
            'Garden.MobileTheme' => 'theme-boilerplate',
            'Routes.DefaultController' => ['categories', 'Internal'],
            'Badges.BadgesModule.Target' => 'AfterUserInfo',
            'Feature.NewFlyouts.Enabled' => true
        ]);
    }

    /**
     * Cleanup when the theme is turned off.
     */
    public function onDisable() {
        saveToConfig([
            'Feature.NewFlyouts.Enabled' => false,
        ]);
    }

    /**
     * Runs every page load
     *
     * @param Gdn_Controller $sender This could be any controller
     *
     * @return void
     */
    public function base_render_before($sender) {
        if (inSection('Dashboard')) {
            return;
        }
    }
}
