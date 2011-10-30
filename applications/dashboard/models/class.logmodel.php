<?php if (!defined('APPLICATION')) exit();
/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 */

class LogModel extends Gdn_Pluggable {
   /// PROPERTIES ///

   protected $_RecalcIDs = array('Discussion' => array());

   /// METHODS ///

   public function Delete($LogIDs) {
      if (!is_array($LogIDs))
         $LogIDs = explode(',', $LogIDs);
      
      // Get the log entries.
      $Logs = Gdn::SQL()->GetWhere('Log', array('LogID' => $LogIDs))->ResultArray();
      $Models = array();
      $Models['Discussion'] = new DiscussionModel();
      $Models['Comment'] = new CommentModel();
      
      foreach ($Logs as $Log) {
         if (in_array($Log['Operation'], array('Spam', 'Moderate')) && array_key_exists($Log['RecordType'], $Models)) {
            // Also delete the record.
            $Model = $Models[$Log['RecordType']];
            $Model->Delete($Log['RecordID'], array('Log' => FALSE));
         }
      }
      
      Gdn::SQL()->WhereIn('LogID', $LogIDs)->Delete('Log');
   }

   // Format the content of a log file.
   public function FormatContent($Log) {
      $Data = $Log['Data'];

      // TODO: Check for a custom log type handler.

      switch ($Log['RecordType']) {
         case 'Discussion':
            $Result =
               '<b>'.$this->FormatKey('Name', $Data).'</b><br />'.
               $this->FormatKey('Body', $Data);
            break;
         case 'Comment':
            $Result = $this->FormatKey('Body', $Data);
            break;
         case 'Registration':
         case 'User':
            $Result = $this->FormatRecord(array('Email', 'Name', 'DiscoveryText'), $Data);
            break;
         default:
            $Result = '';
      }
      return $Result;
   }

   public function FormatKey($Key, $Data) {
      if (isset($Data['_New']) && isset($Data['_New'][$Key])) {
         $Old = Gdn_Format::Text(GetValue($Key, $Data, ''), FALSE);
         $New = Gdn_Format::Text($Data['_New'][$Key], FALSE);
         $Result = $this->FormatDiff($Old, $New);
      } else {
         $Result = Gdn_Format::Text(GetValue($Key, $Data, ''), FALSE);
      }
      return nl2br(trim(($Result)));
   }

   public function FormatRecord($Keys, $Data) {
      $Result = array();
      foreach ($Keys as $Index => $Key) {
         if (is_numeric($Index)) {
            $Index = $Key;
         }

         if (!GetValue($Index, $Data))
            continue;
         $Result[] = '<b>'.htmlspecialchars($Key).'</b>: '.htmlspecialchars(GetValue($Index, $Data));
      }
      $Result = implode('<br />', $Result);
      return $Result;
   }

   public function FormatDiff($Old, $New) {
      static $TinyDiff = NULL;

      if ($TinyDiff === NULL) {
         require_once(dirname(__FILE__).'/tiny_diff.php');
         $TinyDiff = new Tiny_diff();
      }
      
      $Result = $TinyDiff->compare($Old, $New, 'html');
      return $Result;
   }

   public function GetIDs($IDs) {
      if (is_string($IDs))
         $IDs = explode(',', $IDs);

      $Logs = Gdn::SQL()
         ->Select('*')
         ->From('Log')
         ->WhereIn('LogID', $IDs)
         ->Get()->ResultArray();
      foreach ($Logs as &$Log) {
         $Log['Data'] = unserialize($Log['Data']);
      }

      return $Logs;
   }

   public function GetWhere($Where = FALSE, $OrderFields = '', $OrderDirection = 'asc', $Offset = FALSE, $Limit = FALSE) {
      if ($Offset < 0)
         $Offset = 0;

      if (isset($Where['Operation'])) {
         Gdn::SQL()->WhereIn('Operation', (array)$Where['Operation']);
         unset($Where['Operation']);
      }

      $Result = Gdn::SQL()
         ->Select('l.*')
         ->Select('ru.Name as RecordName, iu.Name as InsertName')
         ->From('Log l')
         ->Join('User ru', 'l.RecordUserID = ru.UserID', 'left')
         ->Join('User iu', 'l.InsertUserID = iu.UserID', 'left')
         ->Where($Where)
         ->Limit($Limit, $Offset)
         ->OrderBy($OrderFields, $OrderDirection)
         ->Get()->ResultArray();

      // Deserialize the data.
      foreach ($Result as &$Row) {
         $Row['Data'] = unserialize($Row['Data']);
      }

      return $Result;
   }

   public function GetCountWhere($Where) {
      if (isset($Where['Operation'])) {
         Gdn::SQL()->WhereIn('Operation', (array)$Where['Operation']);
         unset($Where['Operation']);
      }

      $Result = Gdn::SQL()
         ->Select('l.LogID', 'count', 'CountLogID')
         ->From('Log l')
         ->Where($Where)
         ->Get()->Value('CountLogID', 0);

      return $Result;
   }

   /**
    * Log an operation into the log table.
    *
    * @param string $Operation The operation being performed. This is usually one of:
    *  - Delete: The record has been deleted.
    *  - Edit: The record has been edited.
    *  - Spam: The record has been marked spam.
    *  - Moderate: The record requires moderation.
    * @param string $RecordType The type of record being logged. This usually correspond to the tablename of the record.
    * @param array $Data The record data.
    *  - If you are logging just one row then pass the row as an array.
    *  - You can pass an additional _New element to tell the logger what the new data is.
    * @return int The log id.
    */
   public static function Insert($Operation, $RecordType, $Data, $Options = array()) {
      // Check to see if we are storing two versions of the data.
      if (($InsertUserID = self::_LogValue($Data, 'Log_InsertUserID')) === NULL) {
         $InsertUserID = Gdn::Session()->UserID;
      }
      if (($InsertIPAddress = self::_LogValue($Data, 'Log_InsertIPAddress')) == NULL) {
         $InsertIPAddress = Gdn::Request()->IPAddress();
      }
      // Do some known translations for the parent record ID.
      if (($ParentRecordID = self::_LogValue($Data, 'ParentRecordID')) === NULL) {
         switch ($RecordType) {
            case 'Activity':
               $ParentRecordID = self::_LogValue($Data, 'CommentActivityID', 'CommentActivityID');
               break;
            case 'Comment':
               $ParentRecordID = self::_LogValue($Data, 'DiscussionID', 'DiscussionID');
               break;
         }
      }

      // Get the row information from the data or determine it based on the type.
      $LogRow = array(
          'Operation' => $Operation,
          'RecordType' => $RecordType,
          'RecordID' => self::_LogValue($Data, 'RecordID', $RecordType.'ID'),
          'RecordUserID' => self::_LogValue($Data, 'RecordUserID', 'UpdateUserID', 'InsertUserID'),
          'RecordIPAddress' => self::_LogValue($Data, 'RecordIPAddress', 'LastIPAddress', 'InsertIPAddress'),
          'RecordDate' => self::_LogValue($Data, 'RecordDate', 'DateUpdated', 'DateInserted'),
          'InsertUserID' => $InsertUserID,
          'InsertIPAddress' => $InsertIPAddress,
          'DateInserted' => Gdn_Format::ToDateTime(),
          'ParentRecordID' => $ParentRecordID,
          'Data' => serialize($Data)
      );
      if ($LogRow['RecordDate'] == NULL)
         $LogRow['RecordDate'] = Gdn_Format::ToDateTime();

      $GroupBy = GetValue('GroupBy', $Options);
      
      // Make sure we aren't grouping by null values.
      if (is_array($GroupBy)) {
         foreach ($GroupBy as $Name) {
            if (GetValue($Name, $LogRow) === NULL) {
               $GroupBy = FALSE;
               break;
            }
         }
      }

      if ($GroupBy) {
         $GroupBy[] = 'Operation';
         $GroupBy[] = 'RecordType';

         // Check to see if there is a record already logged here.
         $Where = array_combine($GroupBy, ArrayTranslate($LogRow, $GroupBy));
         $LogRow2 = Gdn::SQL()->GetWhere('Log', $Where)->FirstRow(DATASET_TYPE_ARRAY);
         if ($LogRow2) {
            $LogID = $LogRow2['LogID'];

            $OtherUserIDs = explode(',',$LogRow2['OtherUserIDs']);
            if (!is_array($OtherUserIDs))
               $OtherUserIDs = array();
            
            if ($InsertUserID != $LogRow2['InsertUserID'] && !in_array($InsertUserID, $OtherUserIDs)) {
               $OtherUserIDs[] = $InsertUserID;
            }
            
            if (array_key_exists('OtherUserIDs', $Options)) {
               $OtherUserIDs = array_merge($OtherUserIDs, $Options['OtherUserIDs']);
               $OtherUserIDs = array_unique($OtherUserIDs);
               $OtherUserIDs = array_diff($OtherUserIDs, array($InsertUserID));
               
               $Count = count($OtherUserIDs) + 1;
            } else {
               $Count = (int)$LogRow2['CountGroup'] + 1;
            }
            
            Gdn::SQL()->Put(
               'Log',
               array('OtherUserIDs' => implode(',', $OtherUserIDs), 'CountGroup' => $Count, 'DateUpdated' => Gdn_Format::ToDateTime()),
               array('LogID' => $LogID));
         } else {
            $LogID = Gdn::SQL()->Insert('Log', $LogRow);
         }
      } else {
         // Insert the log entry.
         $LogID = Gdn::SQL()->Insert('Log', $LogRow);
      }
      return $LogID;
   }

   public static function LogChange($Operation, $RecordType, $NewData, $OldData = NULL) {
      $RecordID = isset($NewData['RecordID']) ? $NewData['RecordID'] : $NewData[$RecordType.'ID'];

      // Grab the record from the DB.
      if ($OldData == NULL) {
         $OldData = Gdn::SQL()->GetWhere($RecordType, array($RecordType.'ID' => $RecordID))->ResultArray();
      } elseif (!isset($OldData[0]))
         $OldData = array($OldData);

      foreach ($OldData as $Row) {
         
         // Don't log the change if it's right after an insert.
         if (isset($Row['DateInserted']) && (time() - Gdn_Format::ToTimestamp($Row['DateInserted'])) < C('Garden.Log.FloodControl', 20) * 60)
            continue;

         $Row['_New'] = $NewData;
         self::Insert($Operation, $RecordType, $Row);
      }
   }

   protected static function _LogValue($Data, $LogKey, $BakKey1 = '', $BakKey2 = '') {
      if (isset($Data[$LogKey]) && $LogKey != $BakKey1) {
         $Result = $Data[$LogKey];
         unset($Data[$LogKey]);
      } elseif (isset($Data['_New'][$BakKey1])) {
         $Result = $Data['_New'][$BakKey1];
      } elseif (isset($Data[$BakKey1]) && ($Data[$BakKey1] || !$BakKey2)) {
         $Result = $Data[$BakKey1];
      } elseif (isset($Data[$BakKey2])) {
         $Result = $Data[$BakKey2];
      } else {
         $Result = NULL;
      }

      return $Result;
   }

   public function Recalculate() {
      $DiscussionIDs = $this->_RecalcIDs['Discussion'];
      if (count($DiscussionIDs) == 0)
         return;

      $In = implode(',', array_keys($DiscussionIDs));
      $Px = Gdn::Database()->DatabasePrefix;
      $Sql = "update {$Px}Discussion d set d.CountComments = (select coalesce(count(c.CommentID), 0) + 1 from {$Px}Comment c where c.DiscussionID = d.DiscussionID) where d.DiscussionID in ($In)";
      Gdn::Database()->Query($Sql);

      $this->_RecalcIDs['Discussion'] = array();
   }

   public function Restore($Log, $DeleteLog = TRUE) {
      static $Columns = array();

      if (is_numeric($Log)) {
         // Grab the log.
         $LogID = $Log;
         $Log = Gdn::SQL()->GetWhere('Log', array('LogID' => $LogID))->FirstRow(DATASET_TYPE_ARRAY);
         if (!$Log) {
            throw NotFoundException('Log');
         }
      }

      // Throw an event to see if the restore is being overridden.
      $Handled = FALSE;
      $this->EventArguments['Handled'] =& $Handled;
      $this->EventArguments['Log'] =& $Log;
      $this->FireEvent('BeforeRestore');
      if ($Handled)
         return; // a plugin handled the restore.

      if ($Log['RecordType'] == 'Registration')
         $TableName = 'User';
      else
         $TableName = $Log['RecordType'];

      $Data = $Log['Data'];
      if (!isset($Columns[$TableName])) {
         $Columns[$TableName] = Gdn::SQL()->FetchColumns($TableName);
      }
      
      $Set = array_flip($Columns[$TableName]);
      // Set the sets from the data.
      foreach ($Set as $Key => $Value) {
         if (isset($Data[$Key]))
            $Set[$Key] = $Data[$Key];
         else
            unset($Set[$Key]);
      }

      switch ($Log['Operation']) {
         case 'Edit':
            // We are restoring an edit so just update the record.
            $IDColumn = $Log['RecordType'].'ID';
            $Where = array($IDColumn => $Log['RecordID']);
            unset($Set[$IDColumn]);
            Gdn::SQL()->Put(
               $TableName,
               $Set,
               $Where);

            break;
         case 'Delete':
         case 'Spam':
         case 'Moderate':
            $IDColumn = $Log['RecordType'].'ID';
            
            if (!$Log['RecordID']) {
               // This log entry was never in the table.
//               unset($TableName);
               if (isset($Set['DateInserted'])) {
                  $Set['DateInserted'] = Gdn_Format::ToDateTime();
               }
            }

            // Insert the record back into the db.

            if ($Log['Operation'] == 'Spam' && $Log['RecordType'] == 'Registration') {
               SaveToConfig(array('Garden.Registration.NameUnique' => FALSE, 'Garden.Registration.EmailUnique' => FALSE), '', FALSE);
               $ID = Gdn::UserModel()->InsertForBasic($Set, FALSE, array('ValidateSpam' => FALSE));
               if (!$ID) {
                  throw new Exception(Gdn::UserModel()->Validation->ResultsText());
               } else {
                  Gdn::UserModel()->SendWelcomeEmail($ID, '', 'Register');
               }
            } else {
               $ID = Gdn::SQL()
                  ->Options('Ignore', TRUE)
                  ->Insert($TableName, $Set);
               if (!$ID && isset($Log['RecordID']))
                  $ID = $Log['RecordID'];
            }
            
            // Keep track of a discussion ID so that it's count can be recalculated.
            switch ($Log['RecordType']) {
               case 'Discussion':
                  $this->_RecalcIDs['Discussion'][$Log['RecordID']] = TRUE;
                  break;
               case 'Comment':
                  $this->_RecalcIDs['Discussion'][$Log['ParentRecordID']] = TRUE;
                  break;
            }

            break;
      }

      if ($DeleteLog)
         Gdn::SQL()->Delete('Log', array('LogID' => $Log['LogID']));
   }
}