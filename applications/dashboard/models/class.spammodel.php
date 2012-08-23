<?php if (!defined('APPLICATION')) exit();
/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 */

class SpamModel extends Gdn_Pluggable {
   /// PROPERTIES ///
   protected static $_Instance;
   
   public static $Disabled = FALSE;


   /// METHODS ///

   protected static function _Instance() {
      if (!self::$_Instance)
         self::$_Instance = new SpamModel();

      return self::$_Instance;
   }

   /**
    * Check whether or not the record is spam.
    * @param string $RecordType By default, this should be one of the following:
    *  - Comment: A comment.
    *  - Discussion: A discussion.
    *  - User: A user registration.
    * @param array $Data The record data.
    * @param array $Options Options for fine-tuning this method call.
    *  - Log: Log the record if it is found to be spam.
    */
   public static function IsSpam($RecordType, $Data, $Options = array()) {
      if (self::$Disabled)
         return FALSE;
      
      // Set some information about the user in the data.
      if ($RecordType == 'Registration') {
         TouchValue('Username', $Data, $Data['Name']);
      } else {
         TouchValue('InsertUserID', $Data, Gdn::Session()->UserID);
         
         $User = Gdn::UserModel()->GetID(GetValue('InsertUserID', $Data), DATASET_TYPE_ARRAY);
         
         if ($User) {
            if (GetValue('Verified', $User)) {
               // The user has been verified and isn't a spammer.
               return FALSE;
            }
            TouchValue('Username', $Data, $User['Name']);
            TouchValue('Email', $Data, $User['Email']);
            TouchValue('IPAddress', $Data, $User['LastIPAddress']);
         }
      }
      
      if (!isset($Data['Body']) && isset($Data['Story'])) {
         $Data['Body'] = $Data['Story'];
      }
      
      TouchValue('IPAddress', $Data, Gdn::Request()->IpAddress());

      $Sp = self::_Instance();
      
      $Sp->EventArguments['RecordType'] = $RecordType;
      $Sp->EventArguments['Data'] =& $Data;
      $Sp->EventArguments['Options'] =& $Options;
      $Sp->EventArguments['IsSpam'] = FALSE;

      $Sp->FireEvent('CheckSpam');
      $Spam = $Sp->EventArguments['IsSpam'];

      // Log the spam entry.
      if ($Spam && GetValue('Log', $Options, TRUE)) {
         $LogOptions = array();
         switch ($RecordType) {
            case 'Registration':
               $LogOptions['GroupBy'] = array('RecordIPAddress');
               break;
            case 'Comment':
            case 'Discussion':
            case 'Activity':
            case 'ActivityComment':
               $LogOptions['GroupBy'] = array('RecordID');
               break;
         }

         LogModel::Insert('Spam', $RecordType, $Data, $LogOptions);
      }

      return $Spam;
   }
}