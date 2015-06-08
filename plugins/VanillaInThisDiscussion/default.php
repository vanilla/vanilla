<?php
/**
 * InThisDiscussion plugin.
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package InThisDiscussion
 */

// Define the plugin:
$PluginInfo['VanillaInThisDiscussion'] = array(
    'Name' => 'In This Discussion',
    'Description' => "Adds a list of users taking part in the discussion to the side panel of the discussion page in Vanilla.",
    'Version' => '1',
    'Author' => "Mark O'Sullivan",
    'AuthorEmail' => 'mark@vanillaforums.com',
    'AuthorUrl' => 'http://markosullivan.ca',
    'SettingsPermission' => 'Garden.Settings.Manage',
    'SettingsUrl' => '/settings/inthisdiscussion'
);

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
        $Sender->addSideMenu('dashboard/settings/plugins');

        $Conf = new ConfigurationModule($Sender);
        $Conf->initialize(array(
            'Plugins.VanillaInThisDiscussion.Limit' => array(
                'Description' => t('User Limit'),
                'Default' => 20,
                'LabelCode' => t('Enter a limit for the number of users displayed')
            )
        ));

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
