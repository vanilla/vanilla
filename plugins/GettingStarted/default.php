<?php if (!defined('APPLICATION')) exit();

// Define the plugin:
$PluginInfo['GettingStarted'] = array(
   'Name' => 'Getting Started',
   'Description' => 'Adds a welcome message to the dashboard showing new administrators things they can do to get started using their forum. Checks off each item as it is completed.',
   'Version' => '1',
   'Author' => "Mark O'Sullivan",
   'AuthorEmail' => 'mark@vanillaforums.com',
   'AuthorUrl' => 'http://vanillaforums.com'
);

class GettingStartedPlugin extends Gdn_Plugin {

/*
   This plugin should:
   
   1. Display 5 tips for getting started on the dashboard
   2. Check off each item as it is completed
   3. Disable itself when "dismiss" is clicked
*/    
    
   // Adds a "My Forums" menu option to the dashboard area
   public function SettingsController_Render_Before(&$Sender) {
      // Save the action if editing registration settings
      if (strcasecmp($Sender->RequestMethod, 'registration') == 0 && $Sender->Form->AuthenticatedPostBack() === TRUE)
         $this->SaveStep('Plugins.GettingStarted.Registration');

      // Save the action if they reviewed plugins
      if (strcasecmp($Sender->RequestMethod, 'plugins') == 0)
         $this->SaveStep('Plugins.GettingStarted.Plugins');

      // Save the action if they reviewed plugins
      if (strcasecmp($Sender->RequestMethod, 'managecategories') == 0)
         $this->SaveStep('Plugins.GettingStarted.Categories');

      // Add messages & their css on dashboard
      if (strcasecmp($Sender->RequestMethod, 'index') == 0) {
         $Sender->AddCssFile('plugins/GettingStarted/style.css');
         
         $Session = Gdn::Session();
         $WelcomeMessage = '<div class="GettingStarted">'
            .Anchor('×', '/dashboard/plugin/dismissgettingstarted/'.$Session->TransientKey(), 'Dismiss')
   ."<p>Here's how to get started:</p>"
   .'<ul>
      <li class="One'.(Gdn::Config('Plugins.GettingStarted.Registration', '0') == '1' ? ' Done' : '').'">'.Anchor(T('Define how users register for your forum'), '/settings/registration').'</li>
      <li class="Two'.(Gdn::Config('Plugins.GettingStarted.Plugins', '0') == '1' ? ' Done' : '').'">'.Anchor(T('Manage your plugins'), 'settings/plugins').'</li>
      <li class="Three'.(Gdn::Config('Plugins.GettingStarted.Categories', '0') == '1' ? ' Done' : '').'">'.Anchor(T('Organize your discussion categories'), 'vanilla/settings/managecategories').'</li>
      <li class="Four'.(Gdn::Config('Plugins.GettingStarted.Profile', '0') == '1' ? ' Done' : '').'">'.Anchor(T('Customize your profile'), 'profile').'</li>
      <li class="Five'.(Gdn::Config('Plugins.GettingStarted.Discussion', '0') == '1' ? ' Done' : '').'">'.Anchor(T('Start your first discussion'), 'post/discussion').'</li>
   </ul>
</div>';
         $Sender->AddAsset('Messages', $WelcomeMessage, 'WelcomeMessage');
      }
   }
   
   // Record when the various actions are taken
   // 1. If the user edits the registration settings
   public function SaveStep($Step) {
      if (Gdn::Config($Step, '') != '1')
         SaveToConfig($Step, '1');
         
      // If all of the steps are now completed, disable this plugin
      if (
         Gdn::Config('Plugins.GettingStarted.Registration', '0') == '1'
         && Gdn::Config('Plugins.GettingStarted.Plugins', '0') == '1'
         && Gdn::Config('Plugins.GettingStarted.Categories', '0') == '1'
         && Gdn::Config('Plugins.GettingStarted.Profile', '0') == '1'
         && Gdn::Config('Plugins.GettingStarted.Discussion', '0') == '1'
      ) {
         Gdn::PluginManager()->DisablePlugin('GettingStarted');
      }
   }
   
   // If the user posts back any forms to their profile, they've completed step 4: profile customization
   public function ProfileController_Render_Before(&$Sender) {
      if (property_exists($Sender, 'Form') && $Sender->Form->AuthenticatedPostBack() === TRUE)
         $this->SaveStep('Plugins.GettingStarted.Profile');
   }

   // If the user starts a discussion, they've completed step 5: profile customization
   public function PostController_Render_Before(&$Sender) {
      if (strcasecmp($Sender->RequestMethod, 'discussion') == 0 && $Sender->Form->AuthenticatedPostBack() === TRUE)
         $this->SaveStep('Plugins.GettingStarted.Discussion');
   }
   
   public function PluginController_DismissGettingStarted_Create(&$Sender) {
      Gdn::PluginManager()->DisablePlugin('GettingStarted');
      echo 'TRUE';
   }
   
   public function Setup() {
      // No setup required.
   }
}