<?php if (!defined('APPLICATION')) exit();

// Define the plugin:
$PluginInfo['GettingStarted'] = array(
   'Name' => 'Getting Started',
   'Description' => 'Adds a welcome message to the dashboard showing new administrators things they can do to get started using their forum. Checks off each item as it is completed.',
   'Version' => '1',
   'Author' => "Mark O'Sullivan",
   'AuthorEmail' => 'mark@vanillaforums.com',
   'AuthorUrl' => 'http://vanillaforums.com',
   'Hidden' => TRUE
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
      // Have they visited their dashboard?
      if (strtolower($Sender->RequestMethod) != 'index')
         $this->SaveStep('Plugins.GettingStarted.Dashboard');
         
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
   ."<h1>Here's how to get started:</h1>"
   .'<ul>
      <li class="One'.(C('Plugins.GettingStarted.Dashboard', '0') == '1' ? ' Done' : '').'">
         <strong>'.Anchor('Welcome to your Dashboard', 'settings').'</strong>
         <p>This is the administrative dashboard for your new community. Check
         out the configuration options to the left: from here you can configure
         how your community works. <b>Only users in the "Administrator" role can
         see this part of your community.</b></p>
      </li>
      <li class="Two'.(C('Plugins.GettingStarted.Discussions', '0') == '1' ? ' Done' : '').'">
         <strong>'.Anchor("Where is your Community Forum?", '/').'</strong>
         <p>Access your community forum by clicking the "Visit Site" link on the
         top-left of this page, or by '.Anchor('clicking here', '/').'. The
         community forum is what all of your users &amp; customers will see when
         they visit '.Anchor(Gdn::Request()->Url('/', TRUE), Gdn::Request()->Url('/', TRUE)).'.</p>
      </li>
      <li class="Three'.(C('Plugins.GettingStarted.Categories', '0') == '1' ? ' Done' : '').'">
         <strong>'.Anchor(T('Organize your Categories'), 'vanilla/settings/managecategories').'</strong>
         <p>Discussion categories are used to help your users organize their
         discussions in a way that is meaningful for your community.</p>
      </li>
      <li class="Four'.(C('Plugins.GettingStarted.Profile', '0') == '1' ? ' Done' : '').'">
         <strong>'.Anchor(T('Customize your Public Profile'), 'profile').'</strong>
         <p>Everyone who signs up for your community gets a public profile page
         where they can upload a picture of themselves, manage their profile
         settings, and track cool things going on in the community. You should
         '.Anchor('customize your profile now', 'profile').'.</p>
      </li>
      <li class="Five'.(C('Plugins.GettingStarted.Discussion', '0') == '1' ? ' Done' : '').'">
         <strong>'.Anchor(T('Start your First Discussion'), 'post/discussion').'</strong>
         <p>Get the ball rolling in your community by '
         .Anchor('starting your first discussion', 'post/discussion').' now.</p>
      </li>
      <li class="Six'.(C('Plugins.GettingStarted.Plugins', '0') == '1' ? ' Done' : '').'">
         <strong>'.Anchor(T('Manage your Plugins'), 'settings/plugins').'</strong>
         <p>Change the way your community works with plugins. We\'ve bundled
         popular plugins with the software, and there are more available online.</p>
      </li>
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