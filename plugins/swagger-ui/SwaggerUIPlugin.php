<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace Vanilla\SwaggerUI;

use AssetModel;
use Gdn_Plugin;
use SettingsController;
use Vanilla\Addon;

/**
 * Handles the swagger UI menu options.
 */
class SwaggerUIPlugin extends Gdn_Plugin {
    /**
     * Adds "API v2" menu option to the Forum menu on the dashboard.
     *
     * @param \Gdn_Controller $sender The settings controller.
     */
    public function base_getAppSettingsMenuItems_handler($sender) {
        $menu = $sender->EventArguments['SideMenu'];
        $menu->addLink('Site Settings', t('API v2', 'API <span class="nav-pill">v2</span>'), '/settings/swagger', 'Garden.Settings.Manage', ['class' => 'nav-swagger']);
    }

    /**
     * The main swagger page.
     *
     * @param SettingsController $sender The page controller.
     */
    public function settingsController_swagger_create(SettingsController $sender) {
        $sender->permission('Garden.Settings.Manage');

        $folder = 'plugins/'.$this->getAddon()->getKey();

        $relScripts = ['js/custom.js'];
        $js = [];
        foreach ($relScripts as $path) {
            $search = AssetModel::jsPath($path, $folder);
            if (!$search) {
                continue;
            }
            list($path, $url) = $search;
            $js[] = asset($url, false, true);
        }
        $sender->setData('js', $js);

        $sender->addCssFile('swagger-ui.css', $folder);

        $sender->title(t('Vanilla API v2'));
        $sender->render('swagger', 'settings', $folder);
    }
}
