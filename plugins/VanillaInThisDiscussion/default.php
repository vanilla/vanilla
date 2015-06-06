<?php if (!defined('APPLICATION')) {
    exit();
      }
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
    'Requires' => false, // This would normally be an array of plugin names/versions that this plugin requires
    'HasLocale' => false,
    'Author' => "Mark O'Sullivan",
    'AuthorEmail' => 'mark@vanillaforums.com',
    'AuthorUrl' => 'http://markosullivan.ca',
    'RegisterPermissions' => false,
    'SettingsPermission' => 'Garden.Settings.Manage',
    'SettingsUrl' => '/settings/inthisdiscussion'
);

/**
 * Class VanillaInThisDiscussionPlugin
 */
class VanillaInThisDiscussionPlugin extends Gdn_Plugin {

    // Setup settings page
    public function SettingsController_InThisDiscussion_Create($Sender) {
        $Sender->Permission('Garden.Settings.Manage');
        $Sender->SetData('Title', T('In This Discussion Settings'));
        $Sender->AddSideMenu('dashboard/settings/plugins');

        $Conf = new ConfigurationModule($Sender);
        $Conf->Initialize(array(
            'Plugins.VanillaInThisDiscussion.Limit' => array(
                'Description' => T('User Limit'),
                'Default' => 20,
                'LabelCode' => T('Enter a limit for the number of users displayed')
            )
        ));


        $Conf->RenderAll();
    }

    public function DiscussionController_BeforeDiscussionRender_Handler($Sender) {
        // Handle limit
        $Limit = C('Plugins.VanillaInThisDiscussion.Limit', 20);

        // Render
        include_once(PATH_PLUGINS.DS.'VanillaInThisDiscussion'.DS.'class.inthisdiscussionmodule.php');
        $InThisDiscussionModule = new InThisDiscussionModule($Sender);
        $InThisDiscussionModule->GetData($Sender->Data('Discussion.DiscussionID'), $Limit);
        $Sender->AddModule($InThisDiscussionModule);

    }

    public function Setup() {
        // No setup required
    }
}
