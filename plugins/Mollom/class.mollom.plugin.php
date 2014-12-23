<?php if (!defined('APPLICATION')) exit();
/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license GNU GPL2
 */

// Define the plugin:
$PluginInfo['Mollom'] = array(
   'Name' => 'Mollom',
   'Description' => 'Mollom spam protection integration for Vanilla.',
   'Version' => '1.0b',
   'RequiredApplications' => array('Vanilla' => '2.1'),
   'SettingsUrl' => '/settings/mollom',
   'SettingsPermission' => 'Garden.Settings.Manage',
   'Author' => 'Lincoln Russell',
   'AuthorEmail' => 'lincoln@vanillaforums.com'
);

class MollomPlugin extends Gdn_Plugin {
   /// PROPERTIES ///

   /// METHODS ///
   
   /**
    * @return Akismet
    */
   public static function Mollom() {
      static $Mollom;
      if (!$Mollom)
         $Mollom = new MollomVanilla();

      return $Mollom;
   }

   public function CheckMollom($RecordType, $Data) {
      $UserID = $this->UserID();
      if (!$UserID)
         return FALSE;

      $Mollom = self::Mollom();
      
      if (!$Mollom)
         return FALSE;

      $Result = $Mollom->checkContent(array(
         'checks' => array('spam'),
         'postTitle' => GetValue('Name', $Data),
         'postBody' => ConcatSep("\n\n", GetValue('Body', $Data),GetValue('Story', $Data)),
         'authorName' => $Data['Username'],
         'authorEmail' => $Data['Email'],
         'authorIp' => $Data['IPAddress']
      ));
      return ($Result['spamClassification'] == 'spam') ? true : false;
   }
   
   public function Setup() {
      $this->Structure();
   }
   
   public function Structure() {
      // Get a user for operations.
      $UserID = Gdn::SQL()->GetWhere('User', array('Name' => 'Mollom', 'Admin' => 2))->Value('UserID');

      if (!$UserID) {
         $UserID = Gdn::SQL()->Insert('User', array(
            'Name' => 'Mollom',
            'Password' => RandomString('20'),
            'HashMethod' => 'Random',
            'Email' => 'mollom@domain.com',
            'DateInserted' => Gdn_Format::ToDateTime(),
            'Admin' => '2'
         ));
      }
      SaveToConfig('Plugins.Mollom.UserID', $UserID);
   }
   
   public function UserID() {
      return C('Plugins.Mollom.UserID', NULL);
   }

   /// EVENT HANDLERS ///

   public function Base_CheckSpam_Handler($Sender, $Args) {
      if ($Args['IsSpam'])
         return; // don't double check

      $RecordType = $Args['RecordType'];
      $Data =& $Args['Data'];

      $Result = FALSE;
      switch ($RecordType) {
         case 'Registration':
            $Data['Name'] = '';
            $Data['Body'] = GetValue('DiscoveryText', $Data);
            if ($Data['Body']) {
               // Only check for spam if there is discovery text.
               $Result = $this->CheckMollom($RecordType, $Data);
               if ($Result)
                  $Data['Log_InsertUserID'] = $this->UserID();
            }
            break;
         case 'Comment':
         case 'Discussion':
         case 'Activity':
         case 'ActivityComment':
            $Result = $this->CheckMollom($RecordType, $Data);
            if ($Result)
               $Data['Log_InsertUserID'] = $this->UserID();
            break;
         default:
            $Result = FALSE;
      }
      $Sender->EventArguments['IsSpam'] = $Result;
   }

   public function SettingsController_Mollom_Create($Sender, $Args = array()) {
      $Sender->Permission('Garden.Settings.Manage');
      $Sender->SetData('Title', T('Mollom Settings'));

      $Cf = new ConfigurationModule($Sender);
      $Cf->Initialize(array(
          'Plugins.Mollom.publicKey' => array(),
          'Plugins.Mollom.privateKey' => array()
          ));

      $Sender->AddSideMenu('dashboard/settings/plugins');
      $Cf->RenderAll();
   }
}