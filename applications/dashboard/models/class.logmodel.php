<?php if (!defined('APPLICATION')) exit();
/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 */

class LogModel extends Gdn_Pluggable {
   /// PROPERTIES ///

   protected static $_Instance = NULL;
   protected $_RecalcIDs = array('Discussion' => array());
   protected static $_TransactionID = NULL;

   /// METHODS ///
   
   public static function BeginTransaction() {
      self::$_TransactionID = TRUE;
   }

   public function Delete($LogIDs) {
      if (!is_array($LogIDs))
         $LogIDs = explode(',', $LogIDs);
      
      // Get the log entries.
      $Logs = $this->GetIDs($LogIDs);
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
   
   public static function EndTransaction() {
      self::$_TransactionID = NULL;
   }

   // Format the content of a log file.
   public function FormatContent($Log) {
      $Data = $Log['Data'];

      // TODO: Check for a custom log type handler.

      switch ($Log['RecordType']) {
         case 'Activity':
            $Result = $this->FormatKey('Story', $Data);
            break;
         case 'Discussion':
            $Result =
               '<b>'.$this->FormatKey('Name', $Data).'</b><br />'.
               $this->FormatKey('Body', $Data);
            break;
         case 'ActivityComment':
         case 'Comment':
            $Result = $this->FormatKey('Body', $Data);
            break;
         case 'Configuration':
            $Result = $this->FormatConfiguration($Data);
            break;
         case 'Registration':
         case 'User':
            $Result = $this->FormatRecord(array('Email', 'Name'), $Data);
            if ($DiscoveryText = GetValue('DiscoveryText', $Data)) {
               $Result .= '<br /><b>'.T('Why do you want to join?').'</b><br />'.Gdn_Format::Display($DiscoveryText);
            }
            if (GetValue('Banned', $Data)) {
               $Result .= "<br />".T('Banned');
            }
            break;
         default:
            $Result = '';
      }
      return $Result;
   }
   
   public function FormatConfiguration($Data) {
      $Old = $Data;
      $New = $Data['_New'];
      unset($Old['_New']);

      $Old = Gdn_Configuration::Format($Old);
      $New = Gdn_Configuration::Format($New);
      $Diffs = $this->FormatDiff($Old, $New, 'raw');
      
      $Result = array();
      foreach ($Diffs as $Diff) {
         if(is_array($Diff)) {
            if (!empty($Diff['del'])) {
               $Result[] = '<del>'.implode("<br />\n", $Diff['del']).'</del>';
            }
            if (!empty($Diff['ins'])) {
               $Result[] = '<ins>'.implode("<br />\n", $Diff['ins']).'</ins>';
            }
			}
      }
      
      $Result = implode("<br />\n", $Result);
      if ($Result)
         return $Result;
      else
         return T('No Change');
   }

   public function FormatKey($Key, $Data) {
      if (!is_array($Data)) $Data = (array)$Data;
      if (isset($Data['_New']) && isset($Data['_New'][$Key])) {
         $Old = htmlspecialchars(GetValue($Key, $Data, ''));
         $New = htmlspecialchars($Data['_New'][$Key]);
         $Result = $this->FormatDiff($Old, $New);
      } else {
         $Result = htmlspecialchars(GetValue($Key, $Data, ''));
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

   public function FormatDiff($Old, $New, $Method = 'html') {
      static $TinyDiff = NULL;

      if ($TinyDiff === NULL) {
         require_once(dirname(__FILE__).'/tiny_diff.php');
         $TinyDiff = new Tiny_diff();
      }
      
      $Result = $TinyDiff->compare($Old, $New, $Method);
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
         $Log['Data'] = @unserialize($Log['Data']);
         if (!is_array($Log['Data']))
            $Log['Data'] = array();
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
         $Row['Data'] = @unserialize($Row['Data']);
         if (!$Row['Data'])
            $Row['Data'] = array();
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
    * Wrapper for GetCountWhere that takes care of caching specific operation counts.
    * @param string $Operation Comma-delimited list of operation types to get (sum of) counts for.
    */
   public function GetOperationCount($Operation) {
      if ($Operation == 'edits')
         $Operation = array('edit', 'delete');
      else
         $Operation = explode(',', $Operation);
      
      sort($Operation);
      array_map('ucfirst', $Operation);
      $CacheKey = 'Moderation.LogCount.'.implode('.', $Operation);
      $Count = Gdn::Cache()->Get($CacheKey);
      if ($Count === Gdn_Cache::CACHEOP_FAILURE) {
         $Count = $this->GetCountWhere(array('Operation' => $Operation));
         Gdn::Cache()->Store($CacheKey, $Count, array(
            Gdn_Cache::FEATURE_EXPIRY  => 300 // 5 minutes
         ));
      }
      return $Count;
   }   
   

   /**
    * Log an operation into the log table.
    *
    * @param string $Operation The operation being performed. This is usually one of:
    *  - Delete: The record has been deleted.
    *  - Edit: The record has been edited.
    *  - Spam: The record has been marked spam.
    *  - Moderate: The record requires moderation.
    *  - Pending: The record needs pre-moderation.
    * @param string $RecordType The type of record being logged. This usually correspond to the tablename of the record.
    * @param array $Data The record data.
    *  - If you are logging just one row then pass the row as an array.
    *  - You can pass an additional _New element to tell the logger what the new data is.
    * @return int The log id.
    */
   public static function Insert($Operation, $RecordType, $Data, $Options = array()) {
      if ($Operation === FALSE)
         return;
      
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
			 'CategoryID' => self::_LogValue($Data, 'CategoryID'),
          'OtherUserIDs' => implode(',', GetValue('OtherUserIDs', $Options, array())),
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
            $Set = array();
            
            $Data = array_merge(unserialize($LogRow2['Data']), $Data);

            $OtherUserIDs = explode(',',$LogRow2['OtherUserIDs']);
            if (!is_array($OtherUserIDs))
               $OtherUserIDs = array();
            
            if (!$LogRow2['InsertUserID']) {
               $Set['InsertUserID'] = $InsertUserID;
            } elseif ($InsertUserID != $LogRow2['InsertUserID'] && !in_array($InsertUserID, $OtherUserIDs)) {
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
            $Set['OtherUserIDs'] = implode(',', $OtherUserIDs);
            $Set['CountGroup'] = $Count;
            $Set['Data'] = serialize($Data);
            $Set['DateUpdated'] = Gdn_Format::ToDateTime();
            
            if (self::$_TransactionID > 0)
               $Set['TransactionLogID'] = self::$_TransactionID;
            elseif (self::$_TransactionID === TRUE) {
               if ($LogRow2['TransactionLogID'])
                  self::$_TransactionID = $LogRow2['TransactionLogID'];
               else {
                  self::$_TransactionID = $LogID;
                  $Set['TransactionLogID'] = $LogID;
               }
            }
            
            Gdn::SQL()->Put(
               'Log',
               $Set,
               array('LogID' => $LogID));
         } else {
            $L = self::_Instance();
            $L->EventArguments['Log'] =& $LogRow;
            $L->FireEvent('BeforeInsert');
            
            if (self::$_TransactionID > 0)
               $LogRow['TransactionLogID'] = self::$_TransactionID;
            
            $LogID = Gdn::SQL()->Insert('Log', $LogRow);
            
            if (self::$_TransactionID === TRUE) {
               // A new transaction was started and needs to assigned.
               self::$_TransactionID = $LogID;
               Gdn::SQL()->Put('Log', array('TransactionLogID' => $LogID), array('LogID' => $LogID));
            }
            
            $L->EventArguments['LogID'] = $LogID;
            $L->FireEvent('AfterInsert');
         }
      } else {
         if (self::$_TransactionID > 0)
            $LogRow['TransactionLogID'] = self::$_TransactionID;
         
         // Insert the log entry.
         $L = self::_Instance();
         $L->EventArguments['Log'] =& $LogRow;
         $L->FireEvent('BeforeInsert');
         
         $LogID = Gdn::SQL()->Insert('Log', $LogRow);
         
         if (self::$_TransactionID === TRUE) {
            // A new transaction was started and needs to assigned.
            self::$_TransactionID = $LogID;
            Gdn::SQL()->Put('Log', array('TransactionLogID' => $LogID), array('LogID' => $LogID));
         }
         
         $L->EventArguments['LogID'] = $LogID;
         $L->FireEvent('AfterInsert');
      }
      return $LogID;
   }
   
   /**
    *
    * @return LogModel
    */
   protected static function _Instance() {
      if (!self::$_Instance)
         self::$_Instance = new LogModel();

      return self::$_Instance;
   }

   public static function LogChange($Operation, $RecordType, $NewData, $OldData = NULL) {
      $RecordID = isset($NewData['RecordID']) ? $NewData['RecordID'] : GetValue($RecordType.'ID', $NewData);

      // Grab the record from the DB.
      if ($OldData === NULL) {
         $OldData = Gdn::SQL()->GetWhere($RecordType, array($RecordType.'ID' => $RecordID))->ResultArray();
      } elseif (!is_array($OldData))
         $OldData = array($OldData);

      foreach ($OldData as $Row) {
         
         // Don't log the change if it's right after an insert.
         if (GetValue('DateInserted', $Row) && (time() - Gdn_Format::ToTimestamp(GetValue('DateInserted', $Row))) < C('Garden.Log.FloodControl', 20) * 60)
            continue;

         SetValue('_New', $Row, $NewData);
         self::Insert($Operation, $RecordType, $Row);
      }
   }

   protected static function _LogValue($Data, $LogKey, $BakKey1 = '', $BakKey2 = '') {
      $Data = (array)$Data;
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
      if (empty($In))
         return;
      
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
         $Log = $this->GetWhere(array('LogID' => $LogID));
         
         if (!$Log) {
            throw NotFoundException('Log');
         }
         $Log = array_pop($Log);
      }
      
//      decho($Log, 'Log');
      
      $this->_RestoreOne($Log, $DeleteLog);
      // Check for a transaction.
      if ($TransactionID = $Log['TransactionLogID']) {
         $Logs = $this->GetWhere(array('TransactionLogID' => $TransactionID), '', 'asc', 0, 200);
         foreach ($Logs as $LogRow) {
            if ($LogRow['LogID'] == $Log['LogID'])
               continue;
            
            $this->_RestoreOne($LogRow, $DeleteLog);
         }
      }
      // Check for child data.
      if (isset($Log['Data']['_Data'])) {
         $Data = $Log['Data']['_Data'];
         foreach ($Data as $RecordType => $Rows) {
            foreach ($Rows as $Row) {
               $LogRow = array_merge($Log, array('RecordType' => $RecordType, 'Data' => $Row));
               
               if ($RecordType == 'Comment') {
                  $LogRow['ParentRecordID'] = $Row['DiscussionID'];
               }
               
               $this->_RestoreOne($LogRow, FALSE);
            }
         }
      }
      
//      die();
   }
   
   protected function _RestoreOne($Log, $DeleteLog = TRUE) {
      // Throw an event to see if the restore is being overridden.
      $Handled = FALSE;
      $this->EventArguments['Handled'] =& $Handled;
      $this->EventArguments['Log'] =& $Log;
      $this->FireEvent('BeforeRestore');
      if ($Handled)
         return; // a plugin handled the restore.
      
      if ($Log['RecordType'] == 'Configuration') {
         throw new Gdn_UserException('Restoring configuration edits is currently not supported.');
      }

      if ($Log['RecordType'] == 'Registration')
         $TableName = 'User';
      else
         $TableName = $Log['RecordType'];

      $Data = $Log['Data'];
      
      if (isset($Data['Attributes']))
         $Attr = 'Attributes';
      elseif (isset($Data['Data']))
         $Attr = 'Data';
      else
         $Attr = '';
      
      if ($Attr) {
         if (is_string($Data[$Attr]))
            $Data[$Attr] = @unserialize($Data[$Attr]);

         // Record a bit of information about the restoration.
         if (!is_array($Data[$Attr]))
            $Data[$Attr] = array();
         $Data[$Attr]['RestoreUserID'] = Gdn::Session()->UserID;
         $Data[$Attr]['DateRestored'] = Gdn_Format::ToDateTime();
      }
      
//      decho($Data, 'Row being restored');
      
      if (!isset($Columns[$TableName])) {
         $Columns[$TableName] = Gdn::SQL()->FetchColumns($TableName);
      }
      
      $Set = array_flip($Columns[$TableName]);
      // Set the sets from the data.
      foreach ($Set as $Key => $Value) {
         if (isset($Data[$Key])) {
            $Value = $Data[$Key];
            if (is_array($Value))
               $Value = serialize($Value);
            $Set[$Key] = $Value;
         } else
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
         case 'Pending':
         case 'Ban':
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
                  ->Options('Replace', TRUE)
                  ->Insert($TableName, $Set);
               if (!$ID && isset($Log['RecordID']))
                  $ID = $Log['RecordID'];
               
               // Unban a user.
               if ($Log['RecordType'] == 'User' && $Log['Operation'] == 'Ban') {
                  Gdn::UserModel()->SetField($ID, 'Banned', 0);
               }
               
               // Keep track of a discussion ID so that it's count can be recalculated.
               if ($Log['Operation'] != 'Edit') {
                  switch ($Log['RecordType']) {
                     case 'Discussion':
                        $this->_RecalcIDs['Discussion'][$ID] = TRUE;
                        break;
                     case 'Comment':
                        $this->_RecalcIDs['Discussion'][$Log['ParentRecordID']] = TRUE;
                        break;
                  }
               }
            }

            break;
      }
      
      // Fire 'after' event
      if (isset($ID))
         $this->EventArguments['InsertID'] = $ID;
      $this->FireEvent('AfterRestore');

      if ($DeleteLog)
         Gdn::SQL()->Delete('Log', array('LogID' => $Log['LogID']));
      
   }
}