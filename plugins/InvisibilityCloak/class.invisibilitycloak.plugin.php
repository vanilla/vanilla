<?php if (!defined('APPLICATION')) exit();

$PluginInfo['InvisibilityCloak'] = array(
   'Name' => 'Invisibility Cloak',
   'Description' => 'Hide your forum from the prying eyes of search engines and bots while you set it up.',
   'Version' => '1.0',
   'RequiredApplications' => array('Vanilla' => '2.1'),
   'Author' => "Lincoln Russell",
   'AuthorEmail' => 'lincoln@vanillaforums.com'
);

class InvisibilityCloakPlugin extends Gdn_Plugin {
   /**
    * robots.txt.
    */
   public function RootController_Robots_Create($Sender) {
      header("Content-Type: text/plain");
      echo "User-agent: *\nDisallow: /";
   }

   /**
    * No bots meta tag.
    */
   public function Base_Render_Before($Sender) {
      if ($Sender->Head)
         $Sender->Head->AddTag('meta', array('name' => 'robots', 'content' => 'noindex,noarchive'));
   }

   /**
    * Plugin setup.
    */
   public function Setup() {

   }
}
