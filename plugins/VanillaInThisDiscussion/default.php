<?php
/**
 * InThisDiscussion plugin.
 *
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package InThisDiscussion
 */

/**
 * Class VanillaInThisDiscussionPlugin
 */
class VanillaInThisDiscussionPlugin extends Gdn_Plugin {

    /**
     * Setup settings page.
     *
     * @param $Sender
     */
    public function settingsController_inThisDiscussion_create($Sender) {
        $Sender->permission('Garden.Settings.Manage');
        $Sender->setData('Title', t('In This Discussion Settings'));
        $Sender->setHighlightRoute('dashboard/settings/plugins');

        $Conf = new ConfigurationModule($Sender);
        $Conf->initialize([
            'Plugins.VanillaInThisDiscussion.Limit' => [
                'Description' => t('User Limit'),
                'Default' => 20,
                'LabelCode' => t('Enter a limit for the number of users displayed')
            ]
        ]);

        $Conf->renderAll();
    }

    /**
     *
     *
     * @param $Sender
     */
    public function discussionController_beforeDiscussionRender_handler($Sender) {
        // Handle limit
        $Limit = c('Plugins.VanillaInThisDiscussion.Limit', 20);

        // Render
        include_once(PATH_PLUGINS.DS.'VanillaInThisDiscussion'.DS.'class.inthisdiscussionmodule.php');
        $InThisDiscussionModule = new InThisDiscussionModule($Sender);
        $InThisDiscussionModule->getData($Sender->data('Discussion.DiscussionID'), $Limit);
        $Sender->addModule($InThisDiscussionModule);

    }

    /**
     *
     */
    public function setup() {
        // No setup required
    }
}
