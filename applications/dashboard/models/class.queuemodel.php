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


      //add any defaults if missing
      if (!GetValue('Status', $Data)) {
         $Data['Status'] = $this->defaultSaveStatus;
      }
      //collect attributes
      $attributes = array_diff_key($Data, array_flip($this->dbColumns));
      $Data = array_diff_key($Data, $attributes);
      $Data['Attributes'] = json_encode($attributes);


      $this->DefineSchema();
      $SchemaFields = $this->Schema->Fields();

      $SaveData = array();
      $Attributes = array();

      // Grab the current queue item.
      if (isset($Data['QueueID'])) {
         $PrimaryKeyVal = $Data['QueueID'];
         $CurrentItem = $this->SQL->GetWhere('Mod', array('QueueID' => $PrimaryKeyVal))->FirstRow(DATASET_TYPE_ARRAY);
         if ($CurrentItem) {

            $Attributes = @unserialize($CurrentItem['Attributes']);
            if (!$Attributes)
               $Attributes = array();

            $Insert = FALSE;
         } else {
            $Insert = TRUE;
         }
      } else {
         $PrimaryKeyVal = FALSE;
         $Insert = TRUE;
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
         $SaveData['Attributes'] = $Attributes;
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
      $queue = $sql->Get()->ResultArray();
      return $this->CalculateRows($queue);

   }

   public function CalculateRows(&$rows) {
      foreach ($rows as &$row) {
         unset($row['Queue']);
      }
      return $rows;
   }

   public function GetQueueCounts($queue) {

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
            $counts[$row['Status']] = $row['CountStatus'];
         }
         $cache->Store($cacheKey, $counts, array(
               Gdn_Cache::FEATURE_EXPIRY  => $this->countTTL,
               Gdn_Cache::FEATURE_COMPRESS => TRUE
         ));
      } else {
         Trace('Using cached queue counts.');
      }


      return $counts;
   }


}