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

   /**
    * @var int Limits the number af attributes that can be added to an item.
    */
   protected $maxAttributes = 10;

   /**
    * @var array Possible status options.
    */
   protected $statusEnum = array('unread', 'approved', 'denied');

   /**
    * @var int Time to cache the total counts for.
    */
   protected $countTTL = 30;

   /**
    * @var string Default status for adding new items.
    */
   protected $defaultSaveStatus = 'unread';

   /**
    * @var QueueModel
    */
   public static $Instance;

   /**
    * Get an instance of the model.
    *
    * @return QueueModel
    */
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
    */
   public function Save($Data, $Settings = FALSE) {
      $this->DefineSchema();
      $SchemaFields = $this->Schema->Fields();

      $SaveData = array();
      $Attributes = array();

      // Grab the current attachment.
      if (isset($Data['QueueID'])) {
         $PrimaryKeyVal = $Data['QueueID'];
         $Insert = FALSE;
         $CurrentItem = $this->SQL->GetWhere('Queue', array('QueueID' => $PrimaryKeyVal))->FirstRow(DATASET_TYPE_ARRAY);
         if ($CurrentItem) {
            $Attributes = @unserialize($CurrentItem['Attributes']);
            if (!$Attributes)
               $Attributes = array();
         }
      } else {
         $PrimaryKeyVal = FALSE;
         $Insert = TRUE;
      }
      // Grab any values that aren't in the db schema and stick them in attributes.
      foreach ($Data as $Name => $Value) {
         if ($Name == 'Attributes')
            continue;
         if (isset($SchemaFields[$Name])) {
            $SaveData[$Name] = $Value;
         } elseif ($Value === NULL) {
            unset($Attributes[$Name]);
         } else {
            $Attributes[$Name] = $Value;
         }
      }
      $attributeCount = sizeof($Attributes);
      if ($attributeCount > $this->maxAttributes) {
         throw new Gdn_UserException('Maximum number of attributes exceeded (' . $this->maxAttributes . ').');
      } elseif ($attributeCount > 0) {
         $SaveData['Attributes'] = $Attributes;
      } else {
         $SaveData['Attributes'] = NULL;
      }

      if ($Insert) {
         $this->AddInsertFields($SaveData);
         //add any defaults if missing
         if (!GetValue('Status', $Data)) {
            $SaveData['Status'] = $this->defaultSaveStatus;
         }
      } else {
         $this->AddUpdateFields($SaveData);
         if (GetValue('Status', $Data)) {
            if (Gdn::Session()->UserID) {
               $SaveData['StatusUserID'] = Gdn::Session()->UserID;
               $SaveData['DateStatus'] = Gdn_Format::ToDateTime();
            }
         }
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


   /**
    * {@inheritDoc}
    */
   public function Get($queue, $page = 'p1', $limit = 30, $where = array(), $orderBy = 'DateInserted', $order = 'desc') {
      list($offset, $limit) = OffsetLimit($page, $limit);

      $order = strtolower($order);
      if ($order != 'asc' || $order != 'asc') {
         $order = 'desc';
      }
      $sql = Gdn::SQL();
      $sql->From('Queue')
         ->Limit($limit, $offset);

      $where['Queue'] = $queue;
      foreach ($where as $key => $value) {
         $sql->Where($key, $value);
      }
      $sql->OrderBy($orderBy, $order);
      $Rows = $sql->Get()->ResultArray();
      foreach ($Rows as &$Row) {
         $Row = $this->CalculateRow($Row);
      }
      return $Rows;

   }

   /**
    * Calculate row.
    *
    * @param Array $Row Row from the database.
    * @return array Modififed Row
    */
   protected function CalculateRow($Row) {
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

      return $Row;

   }

   /**
    * Get Queue Counts.
    *
    * @param string $queue Name of the queue.
    * @param int $pageSize Number of results per page.
    * @return array
    */
   public function GetQueueCounts($queue, $pageSize = 30, $where = array()) {

      $where['Queue'] = $queue;
      $cacheKeyFormat = 'Queue:Count:';

      foreach ($where as $key => $value) {
         $cacheKeyFormat .= $key . ':' . $value .':';
      }
      $cacheKeyFormat .= '{queue}';
      $cache = Gdn::Cache();

      $cacheKey = FormatString($cacheKeyFormat, array('queue' => $queue));
      $counts = $cache->Get($cacheKey, array(Gdn_Cache::FEATURE_COMPRESS => TRUE));

      if (!$counts) {
         $sql = Gdn::SQL();
         $sql->Select('Status')
            ->Select('Status', 'count', 'CountStatus')
            ->From('Queue');
         foreach ($where as $key => $value) {
            $sql->Where($key, $value);
         }

         $sql->GroupBy('Status');
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

   /**
    * Get list of possible statuses.
    *
    * @return array
    */
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