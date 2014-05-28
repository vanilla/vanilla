<?php
/**
 * @copyright 2009-2014 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 */

/**
 * Class QueueModel.
 */
class QueueModel extends Gdn_Model {

   protected $dbColumns = array(
      'QueueID',
      'Queue',
      'DateInserted',
      'InsertUserID',
      'Name',
      'Body',
      'Format',
      'CategoryID',
      'ForeignType',
      'ForeignID',
      'ForeignUserID',
      'Status',
      'DateStatus',
      'DateStatusUserID',
      'Attributes'
   );

   protected $statusEnum = array('unread', 'approved', 'denied');

   protected $countTTL = 30;

   protected $defaultSaveStatus = 'unread';

   public static $Instance;

   public static function Instance() {
      if (isset(self::$Instance)) {
         return self::$Instance;
      }
      self::$Instance = new QueueModel();
      return self::$Instance;
   }

   public function __construct() {
      parent::__construct('Queue');
      $this->PrimaryKey = 'QueueID';
   }

   /**
    * {@inheritDoc}
    * @param array $Data
    * @param bool $Settings
    * @return bool|mixed Primary Key Value
    */
   public function Save($Data, $Settings = FALSE) {

      //collect attributes
      $Attributes = array_diff_key($Data, array_flip($this->dbColumns));
      $Data = array_diff_key($Data, $Attributes);

      $this->DefineSchema();
      $SchemaFields = $this->Schema->Fields();

      $SaveData = array();
      // Grab the current queue item.

      if (isset($Data['QueueID'])) {
         $PrimaryKeyVal = $Data['QueueID'];
         $CurrentItem = $this->SQL->GetWhere('Queue', array('QueueID' => $PrimaryKeyVal))->FirstRow(DATASET_TYPE_ARRAY);
         if ($CurrentItem) {
            $CurrentAttributes = @unserialize($CurrentItem['Attributes']);
            if ($CurrentAttributes) {
               $Attributes =  $CurrentAttributes + $Attributes;
            }
            $Insert = FALSE;
            if (isset($Data['Status']) && $Data['Status'] != $CurrentItem['Status']) {
               $Data['DateStatus'] = Gdn_Format::ToDateTime();
               if (Gdn::Session()->UserID) {
                  $Data['DateStatus'] = Gdn::Session()->UserID;
               }

            }
         } else {
            $Insert = TRUE;
         }
      } else {
         $PrimaryKeyVal = FALSE;
         $Insert = TRUE;
      }

      //add any defaults if missing
      if (!GetValue('Status', $Data)) {
         $Data['Status'] = $this->defaultSaveStatus;
      }
      // Grab any values that aren't in the db schema and stick them in attributes.
      foreach ($Data as $Name => $Value) {
         if ($Name == 'Attributes') {
            continue;
         }
         if (isset($SchemaFields[$Name])) {
            $SaveData[$Name] = $Value;
         } elseif ($Value === NULL) {
            unset($Attributes[$Name]);
         } else {
            $Attributes[$Name] = $Value;
         }
      }
      if (sizeof($Attributes)) {
         $SaveData['Attributes'] = serialize($Attributes);
      } else {
         $SaveData['Attributes'] = NULL;
      }
      if ($Insert) {
         $this->AddInsertFields($SaveData);
      } else {
         $this->AddUpdateFields($SaveData);
      }

      // Validate the form posted values.
      if ($this->Validate($SaveData, $Insert) === TRUE) {
         $Fields = $this->Validation->ValidationFields();

         if ($Insert === FALSE) {
            $Fields = RemoveKeyFromArray($Fields, $this->PrimaryKey); // Don't try to update the primary key
            $this->Update($Fields, array($this->PrimaryKey => $PrimaryKeyVal));
         } else {
            $PrimaryKeyVal = $this->Insert($Fields);
         }
      } else {
         $PrimaryKeyVal = FALSE;
      }
      return $PrimaryKeyVal;
   }

   public function Get($queue, $page = 'p1', $limit = 30, $where = array()) {
      list($offset, $limit) = OffsetLimit($page, $limit);
      $sql = Gdn::SQL();
      $sql->From('Queue')
         ->Limit($limit, $offset);

      $sql->Where('Queue', $queue);

      $sql->OrderBy('DateInserted', 'desc');

      foreach ($where as $key => $value) {
         $sql->Where($key, $value)  ;
      }
      $Rows = $sql->Get()->ResultArray();
      foreach ($Rows as &$Row) {
         $Row = $this->CalculateRow($Row);
      }
      return $Rows;

   }

   protected function CalculateRow(&$Row) {
      if (isset($Row['Attributes']) && !empty($Row['Attributes'])) {
         if (is_array($Row['Attributes'])) {
            $Attributes = $Row['Attributes'];
         } else {
            $Attributes = unserialize($Row['Attributes']);
         }
         if (is_array($Attributes)) {
            $Row = array_replace($Row, $Attributes);
         }
      }
      unset($Row['Attributes']);
      unset($Row['Queue']);

      return $Row;

   }

   public function GetQueueCounts($queue, $pageSize = 30) {

      $cacheKeyFormat = 'Queue:Count:{queue}';
      $cache = Gdn::Cache();

      $cacheKey = FormatString($cacheKeyFormat, array('queue' => $queue));
      $counts = $cache->Get($cacheKey, array(Gdn_Cache::FEATURE_COMPRESS => TRUE));

      if (!$counts) {
         $sql = Gdn::SQL();
         $sql->Select('Status')
            ->Select('Status', 'count', 'CountStatus')
            ->From('Queue')
            ->Where('Queue =', $queue)
            ->GroupBy('Status');
         $rows = $sql->Get()->ResultArray();

         $counts = array();
         foreach ($rows as $row) {
            $counts['Status'][$row['Status']] = (int)$row['CountStatus'];
         }
         foreach ($this->statusEnum as $status) {
            //set empty counts to zero
            if (!GetValueR('Status.' . $status, $counts)) {
               $counts['Status'][$status] = 0;
            }
         }
         $total = 0;
         foreach ($counts['Status'] as $statusTotal) {
            $total += $statusTotal;
         }
         $counts['Records'] = $total;
         $counts['PageSize'] = $pageSize;
         $counts['Pages'] = ceil($total/$pageSize);

         $cache->Store($cacheKey, $counts, array(
               Gdn_Cache::FEATURE_EXPIRY  => $this->countTTL,
               Gdn_Cache::FEATURE_COMPRESS => TRUE
         ));
      } else {
         Trace('Using cached queue counts.');
      }


      return $counts;
   }

   public function getStatuses() {
      return $this->statusEnum;
   }


   /**
    * {@inheritDoc}
    */
   public function GetID($ID, $DatasetType = FALSE, $Options = array()) {
      $Row = parent::GetID($ID, DATASET_TYPE_ARRAY, $Options);
      return $this->CalculateRow($Row);
   }


}