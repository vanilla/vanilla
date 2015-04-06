<?php

if (!defined('APPLICATION'))
   exit();
/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 */
// Define the plugin:
$PluginInfo['StopForumSpam'] = array(
    'Name' => T('Stop Forum Spam'),
    'Description' => T("Got spammer problems? This integrates the spammer blacklist from stopforumspam.com to mitigate the issue."),
    'Version' => '1.0.1',
    'RequiredApplications' => array('Vanilla' => '2.0.18'),
    'Author' => 'Todd Burry',
    'AuthorEmail' => 'todd@vanillaforums.com',
    'AuthorUrl' => 'http://www.vanillaforums.org/profile/todd',
    'SettingsUrl' => '/settings/stopforumspam'
);

class StopForumSpamPlugin extends Gdn_Plugin {

   /// Properties ///
   /// Methods ///

   public static function Check(&$Data, &$Options) {
      // Make the request.
      $Get = array();


     
      if (isset($Data['IPAddress'])) {
         $AddIP = TRUE;
         // Don't check against the localhost.
         foreach (array(
            '127.0.0.1/0',
            '10.0.0.0/8',
            '172.16.0.0/12',
            '192.168.0.0/16') as $LocalCIDR) {

            if (Gdn_Statistics::CIDRCheck($Data['IPAddress'], $LocalCIDR)) {
               $AddIP = FALSE;
               break;
            }
         }
         if ($AddIP)
            $Get['ip'] = $Data['IPAddress'];
      }
      if (isset($Data['Username'])) {
         $Get['username'] = $Data['Username'];
      }
      if (isset($Data['Email'])) {
         $Get['email'] = $Data['Email'];
      }

      if (empty($Get))
         return FALSE;

      $Get['f'] = 'json';

      $Url = "http://www.stopforumspam.com/api?" . http_build_query($Get);

      $Curl = curl_init();
      curl_setopt($Curl, CURLOPT_URL, $Url);
      curl_setopt($Curl, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($Curl, CURLOPT_TIMEOUT, 4);
      curl_setopt($Curl, CURLOPT_FAILONERROR, 1);
      $ResultString = curl_exec($Curl);
      curl_close($Curl);

      if ($ResultString) {
         $Result = json_decode($ResultString, TRUE);

         $IPFrequency = GetValueR('ip.frequency', $Result, 0);
         $EmailFrequency = GetValueR('email.frequency', $Result, 0);

         $IsSpam = FALSE;

         // Flag registrations as spam above a certain threshold.
         if ($IPFrequency >= C('Plugins.StopForumSpam.IPThreshold1', 5) || $EmailFrequency >= C('Plugins.StopForumSpam.EmailThreshold1', 20)) {
            $IsSpam = TRUE;
         }
         
         // Don't even log registrations that are above another threahold.
         if ($IPFrequency >= C('Plugins.StopForumSpam.IPThreshold2', 20) || $EmailFrequency >= C('Plugins.StopForumSpam.EmailThreshold2', 50)) {
            $Options['Log'] = FALSE;
         }

         if ($Result) {
            $Data['_Meta']['IP Frequency'] = $IPFrequency;
            $Data['_Meta']['Email Frequency'] = $EmailFrequency;
         }
         return $IsSpam;
      }

      return FALSE;
   }

   public function Setup() {
      $this->Structure();
   }

   public function Structure() {
      // Get a user for operations.
      $UserID = Gdn::SQL()->GetWhere('User', array('Name' => 'StopForumSpam', 'Admin' => 2))->Value('UserID');

      if (!$UserID) {
         $UserID = Gdn::SQL()->Insert('User', array(
            'Name' => 'StopForumSpam',
            'Password' => RandomString('20'),
            'HashMethod' => 'Random',
            'Email' => 'stopforumspam@domain.com',
            'DateInserted' => Gdn_Format::ToDateTime(),
            'Admin' => '2'
         ));
      }
      SaveToConfig('Plugins.StopForumSpam.UserID', $UserID, array('CheckExisting' => TRUE));
   }

   public function UserID() {
      return C('Plugins.StopForumSpam.UserID', NULL);
   }

   /// Event Handlers ///

   public function Base_CheckSpam_Handler($Sender, $Args) {
      // Don't check for spam if another plugin has already determined it is.
      if ($Sender->EventArguments['IsSpam'])
         return;
      
      $RecordType = $Args['RecordType'];
      $Data =& $Args['Data'];
      $Options =& $Args['Options'];
      
      // Detect our favorite bot and short-circuit
      if ($Reason = GetValue('DiscoveryText', $Data)) {
         if (substr($Reason,0,1) === '{') {
            $Sender->EventArguments['IsSpam'] = TRUE;
            $Data['Log_InsertUserID'] = $this->UserID();
            $Data['RecordIPAddress'] = Gdn::Request()->IpAddress();
            return;
         }
      }

      $Result = FALSE;
      switch ($RecordType) {
         case 'Registration':
            $Result = self::Check($Data, $Options);
            if ($Result) {
               $Data['Log_InsertUserID'] = $this->UserID();
               $Data['RecordIPAddress'] = Gdn::Request()->IpAddress();
            }
            break;
         case 'Comment':
         case 'Discussion':
         case 'Activity':
//            $Result = $this->CheckTest($RecordType, $Data) || $this->CheckStopForumSpam($RecordType, $Data) || $this->CheckAkismet($RecordType, $Data);
            break;
      }
      $Sender->EventArguments['IsSpam'] = $Result;
   }

   public function SettingsController_StopForumSpam_Create($Sender, $Args = array()) {
      $Sender->Permission('Garden.Settings.Manage');
      $Conf = new ConfigurationModule($Sender);
		$Conf->Initialize(array(
			'Plugins.StopForumSpam.IPThreshold1' => array('Type' => 'int', 'Control' => 'TextBox', 'Default' => 5, 'Description' => 'IP addresses reported this many times will be flagged as spam.'),
			'Plugins.StopForumSpam.EmailThreshold1' => array('Type' => 'int', 'Control' => 'TextBox', 'Default' => 20, 'Description' => 'Email addresses reported this many times will be flagged as spam.'),
         'Plugins.StopForumSpam.IPThreshold2' => array('Type' => 'int', 'Control' => 'TextBox', 'Default' => 20, 'Description' => 'IP addresses reported this many times will be completely rejected.'),
			'Plugins.StopForumSpam.EmailThreshold2' => array('Type' => 'int', 'Control' => 'TextBox', 'Default' => 50, 'Description' => 'Email addresses reported this many times will be completely rejected.'),
		));

     $Sender->AddSideMenu('dashboard/settings/plugins');
     $Sender->SetData('Title', T('Stop Forum Spam Settings'));
     $Sender->ConfigurationModule = $Conf;
     $Conf->RenderAll();
   }
}