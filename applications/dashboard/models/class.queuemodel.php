<?php
/**
 * @copyright 2009-2014 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 */

/**
 * Class QueueModel.
 *
 * @todo Add Unique index to ForeignID.
 */
class QueueModel extends Gdn_Model {

   protected $moderatorUserID;

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
   public function Save($data, $Settings = FALSE) {
      $this->DefineSchema();
      $SchemaFields = $this->Schema->Fields();

      $SaveData = array();
      $Attributes = array();

      // Grab the current attachment.
      if (isset($data['QueueID'])) {
         $PrimaryKeyVal = $data['QueueID'];
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
      foreach ($data as $Name => $Value) {
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
         if (!GetValue('Status', $data)) {
            $SaveData['Status'] = $this->defaultSaveStatus;
         }
      } else {
         $this->AddUpdateFields($SaveData);
         if (GetValue('Status', $data)) {
            if (Gdn::Session()->UserID) {
               if (!GetValue('StatusUserID', $SaveData)) {
                  $SaveData['StatusUserID'] = Gdn::Session()->UserID;
               }
               $SaveData['DateStatus'] = Gdn_Format::ToDateTime();
            }
         }
      }
      //format fields
      if (GetValue('Format', $SaveData)) {
         $SaveData['Format'] = strtolower($SaveData['Format']);
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
    * {@inheritDoc}
    */
   public function GetWhere($Where = FALSE, $OrderFields = '', $OrderDirection = 'asc', $Limit = FALSE, $Offset = FALSE) {
      $this->_BeforeGet();

      $results = $this->SQL->GetWhere($this->Name, $Where, $OrderFields, $OrderDirection, $Limit, $Offset);
      foreach($results->ResultArray(DATASET_TYPE_ARRAY) as &$row) {
         $row = $this->CalculateRow($row);
      }
      return $results;
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
   public function GetID($ID, $datasetType = FALSE, $Options = array()) {
      $Row = parent::GetID($ID, DATASET_TYPE_ARRAY, $Options);
      return $this->CalculateRow($Row);
   }

   /**
    * Check if content being posted needs moderation.
    *
    * @param string $recordType Record type.  ie: Discussion, Comment, Activity
    * @param array $data Record data.
    * @param array $Options Additional options.
    * @return bool
    * @throws Gdn_UserException If error updating queue.
    */
   public static function Premoderate($recordType, $data, $Options = array()) {

      $IsPremoderation = FALSE;
      // Allow for Feed Discussions or any other message posted as system.
      if ($data['InsertUserID'] == C('Garden.SystemUserID')) {
         return false;
      }

      $ApprovalRequired = CheckRestriction('Vanilla.Approval.Require');
      if ($ApprovalRequired && !GetValue('Verified', Gdn::Session()->User)) {
         //@todo There is no interface to manage these yet.
         $IsPremoderation = true;
      }

      $Qm = self::Instance();
      $Qm->EventArguments['RecordType'] = $recordType;
      $Qm->EventArguments['Data'] =& $data;
      $Qm->EventArguments['Options'] =& $Options;
      $Qm->EventArguments['Premoderate'] =& $IsPremoderation;

      $Qm->FireEvent('CheckPremoderation');

      $IsPremoderation = $Qm->EventArguments['Premoderate'];

      if ($IsPremoderation) {

         if (GetValue('ForeignID', $Qm->EventArguments)) {
            $data['ForeignID'] = $Qm->EventArguments['ForeignID'];
         }
         $queueRow = self::convertToQueueRow($recordType, $data);
         // Allow InsertUserID to be overwritten
         if (isset($Qm->EventArguments['InsertUserID']) && !$ApprovalRequired) {
            $queueRow['InsertUserID'] = $Qm->EventArguments['InsertUserID'];
         }

         // Save to Queue

         $Saved = $Qm->Save($queueRow);
         if (!$Saved) {
            throw new Gdn_UserException($Qm->Validation->ResultsText());
         }

      }

      return $IsPremoderation;
   }

   /**
    * Approve an item in the queue.
    *
    * @param array|string $queueItem QueueID or array containing queue row.
    * @param bool $doSave Call model save after approve
    * @return bool Item saved.
    * @throws Gdn_UserException Unknown type.
    */
   public function approve($queueItem, $doSave = true) {

      if (!is_array($queueItem)) {
         $queueItem = $this->GetID($queueItem);
      }

      if (!$queueItem) {
         throw new Gdn_UserException("Item not found in queue.", 404);
      }

      if ($queueItem['Status'] != 'unread') {
         Trace('QueueID: ' . $queueItem['QueueID'] . ' already processed.  Skipping.');
         return true;
      }

      if (stristr($queueItem['Queue'], 'testing') !== false) {
         $doSave = false;
      }

      // Content Restore.
      if ($queueItem['Queue'] == 'reported' || $queueItem['Queue'] == 'spam') {

         $logModel = new LogModel();

         switch (strtolower($queueItem['ForeignType'])) {
            case 'discussion':
               $ID = $queueItem['DiscussionID'];
               $logs = $logModel->GetWhere(array(
                     'RecordType' => 'Discussion',
                     'Operation' => 'Delete',
                     'RecordID' => $queueItem['DiscussionID']
                  ));
               break;
            case 'comment':
               $ID = $queueItem['CommentID'];
               $logs = $logModel->GetWhere(array(
                     'RecordType' => 'Comment',
                     'Operation' => 'Delete',
                     'RecordID' => $queueItem['CommentID']
                  ));
               break;

            default:
               Trace('Content not restored.');

         }
         $doSave = false;

         if (count($logs) == 1) {
            $log = reset($logs);
            if (GetValue('LogID', $log)) {
               $logModel->Restore($log);
            }
         } else {
            Trace('Item not found in logs.');
         }

      }
      $ContentType = $queueItem['ForeignType'];

      if ($doSave) {
         $Attributes = false;
         switch(strtolower($ContentType)) {
            case 'comment':
               $model = new CommentModel();
               $Attributes = true;
               break;
            case 'discussion':
               $model = new DiscussionModel();
               $Attributes = true;
               break;
            case 'activity':
               $model = new ActivityModel();
               break;
            case 'activitycomment':
               $model = new ActivityModel();
               break;
            default:
               throw new Gdn_UserException('Unknown Type: ' . $ContentType);
         }

         // Am I approving an item that is already in the system?
         if (GetValue('ForeignID', $queueItem)) {
            $parts = explode('-', $queueItem['ForeignID'], 2);
            $validContentParts = array('A', 'C', 'D', 'AC');
            if (in_array($parts[0], $validContentParts)) {
               $exisiting = $model->GetID($parts[1]);
               if ($exisiting) {
                  Trace('Item has already been added');
                  return true;
               }
            }
         }

         $saveData = $this->convertToSaveData($queueItem);
         if ($Attributes) {
            $saveData['Attributes'] = serialize(
               array(
                  'Moderation' =>
                     array(
                        'Approved' => true,
                        'ApprovedUserID' => $this->getModeratorUserID(),
                        'DateApproved' => Gdn_Format::ToDateTime()
                     )
               )
            );
         }
         $saveData['Approved'] = true;

         if (strtolower($queueItem['ForeignType']) == 'activitycomment') {
            $ID = $model->Comment($saveData);
         } else {
            $ID = $model->Save($saveData);
         }
         // Add the validation results from the model to this one.
         $this->Validation->AddValidationResult($model->ValidationResults());
         $valid = count($this->ValidationResults()) == 0;
         if (!$valid) {
            Trace('QueueID: ' . $queueItem['QueueID'] . ' - ' . $this->Validation->ResultsText());
            return false;
         }

         if (method_exists($model, 'Save2')) {
            $model->Save2($ID, true);
         }
      }
      // Update Queue
      if (empty($foreignID)) {
         $foreignID = $this->generateForeignID(null, $ID, $ContentType);
      }
      $saved = $this->Save(
         array(
            'QueueID' => $queueItem['QueueID'],
            'Status' => 'approved',
            'StatusUserID' => $this->getModeratorUserID(),
            'DateUpdated' => Gdn_Format::ToDateTime(),
            'UpdateUserID' => Gdn::Session()->UserID,
            'ForeignID' => $foreignID,
            'PreviousForeignID' => $queueItem['ForeignID']
         )
      );
      if (!$saved) {
         $this->Validation->AddValidationResult('Error', 'Error updating queue.');
         return false;
      }

      return true;

   }


   /**
    * @param array $where
    * @param string $action
    * @param VanillaController $sender
    * @return bool
    */
   public function approveOrDenyWhere($where, $action = 'approve', $sender = null) {

      if (!is_string($action) || !method_exists($this, $action)) {
         throw new Gdn_UserException('Unknown Method.');
      }

      $queueItems = $this->getQueueItems($where);

      $whereMsg = '';
      foreach ($where as $k => $v) {
         $whereMsg .= "$k = $v and";
      }
      $whereMsg = substr($whereMsg, 0, -3);

      $errors = array();

      if (sizeof($queueItems) == 0) {
         $this->Validation->AddValidationResult('Not Found', $whereMsg);
         return false;
      }
      foreach($queueItems as $item) {
         $valid = $this->{$action}($item);
         if (!$valid) {
            $errors[$whereMsg] = $this->Validation->ResultsText();
         } else {
            switch ($action) {
               case 'approve':
                  $sender->SetData('Approved', $whereMsg);
                  break;
               case 'deny':
                  $sender->SetData('Denied', $whereMsg);
                  break;
               default:
                  $sender->SetData($action, $whereMsg);
            }
         }
         $this->Validation->Results(TRUE);
      }

      foreach ($errors as $id => $value) {
         $this->Validation->AddValidationResult('FieldErrors', "{$id} - $value");
      }

      return sizeof($errors) == 0;
   }

   /**
    * Deny an item from the queue.
    *
    * @param array|string $queueItem QueueID or queue row
    * @return bool true if item was updated
    * @throws Gdn_UserException Item not found
    */
   public function deny($queueItem) {

      if (is_numeric($queueItem)) {
         $queueItem = $this->GetID($queueItem);
      }

      if (!$queueItem) {
         throw new Gdn_UserException("Item not found in queue.", 404);
      }

      if ($queueItem['Status'] != 'unread') {
         Trace('QueueID: ' . $queueItem['QueueID'] . ' already processed.  Skipping.');
         return true;
      }
      $saved = $this->Save(
         array(
            'QueueID' => $queueItem['QueueID'],
            'Status' => 'denied',
            'StatusUserID' => $this->getModeratorUserID()
         )
      );
      if (!$saved) {
         return false;
      }

      return true;

   }

   /**
    * Convert save data to an array that can be saved in the queue.
    *
    * @param string $recordType Record Type. Discussion, Comment, Activity
    * @param array $data Data fields.
    * @return array Row to be saved to the Model.
    * @throws Gdn_UserException On unknown record type.
    */
   public function convertToQueueRow($recordType, $data) {

      $queueRow = array(
         'Queue' => val('Queue', $data, 'premoderation'),
         'Status' => val('Status', $data, 'unread'),
         'ForeignUserID' => val('InsertUserID', $data, Gdn::Session()->UserID),
         'ForeignIPAddress' => val('InsertIPAddress', $data, Gdn::Request()->IpAddress()),
         'Body' => $data['Body'],
         'Format' => val('Format', $data, C('Garden.InputFormatter')),
         'ForeignID' => val('ForeignID', $data, self::generateForeignID($data))
      );

      switch (strtolower($recordType)) {
         case 'comment':
            $DiscussionModel = new DiscussionModel();
            $Discussion = $DiscussionModel->GetID($data['DiscussionID'], DATASET_TYPE_OBJECT);
            $queueRow['ForeignType'] = 'Comment';
            $queueRow['DiscussionID'] = $data['DiscussionID'];
            $queueRow['CategoryID'] = $Discussion->CategoryID;
            $queueRow['Name'] = 'Re: ' . $Discussion->Name;
            break;
         case 'discussion':
            $queueRow['ForeignType'] = 'Discussion';
            $queueRow['Name'] = $data['Name'];
            if (GetValue('Announce', $data)) {
               $queueRow['Announce'] = $data['Announce'];
            }
            if (!GetValue('CategoryID', $data)) {
               throw new Gdn_UserException('CategoryID is a required field for discussions.');
            }
            $queueRow['CategoryID'] = $data['CategoryID'];
            break;
         case 'activity':
            $queueRow['ForeignType'] = 'Activity';
            $queueRow['Body'] = $data['Story'];
            $queueRow['HeadlineFormat'] = $data['HeadlineFormat'];
            $queueRow['RegardingUserID'] = $data['RegardingUserID'];
            $queueRow['ActivityUserID'] = $data['ActivityUserID'];
            $queueRow['ActivityType'] = 'WallPost';
            $queueRow['NotifyUserID'] = ActivityModel::NOTIFY_PUBLIC;
            break;
         case 'activitycomment':
            $queueRow['ForeignType'] = 'ActivityComment';
            $queueRow['ActivityID'] = $data['ActivityID'];
            break;
         default:
            throw new Gdn_UserException('Unknown Type: ' . $recordType);
      }

      return $queueRow;

   }

   /**
    * Convert a queue row to an array to be saved to the model.
    *
    * @param array $queueRow Queue row data.
    * @return array Of data to be saved.
    * @throws Gdn_UserException on unknown ForeignType.
    */
   public function convertToSaveData($queueRow) {
      $data = array(
         'Body' => $queueRow['Body'],
         'Format' => $queueRow['Format'],
         'InsertUserID' => $queueRow['ForeignUserID'],
         'InsertIPAddress' => $queueRow['ForeignIPAddress'],
      );
      switch (strtolower($queueRow['ForeignType'])) {
         case 'comment':
            $data['DiscussionID'] = $queueRow['DiscussionID'];
            $data['CategoryID'] = $queueRow['CategoryID'];
            break;
         case 'discussion':
            $data['Name'] = $queueRow['Name'];
            $data['CategoryID'] = $queueRow['CategoryID'];
            break;
         case 'activity':
            $data['HeadlineFormat'] = $queueRow['HeadlineFormat'];

            if (GetValue('RegardingUserID', $queueRow)) {
               //posting on own wall
               $data['RegardingUserID'] = $queueRow['RegardingUserID'];
            }
            $data['ActivityUserID'] = $queueRow['ActivityUserID'];
            $data['NotifyUserID'] = $queueRow['NotifyUserID'];
            $data['ActivityType'] = $queueRow['ActivityType'];
            $data['Story'] = $queueRow['Body'];

            break;
         case 'activitycomment':
            $data['ActivityID'] = $queueRow['ActivityID'];
            break;
         default:
            throw new Gdn_UserException('Unknown Type');
      }

      return $data;
   }

   /**
    * Get items from the queue.
    *
    * @param array $where key value pair for where clause.
    * @return array|null
    */
   protected function getQueueItems($where) {
      $queueItems = $this->GetWhere($where)->ResultArray();
      return $queueItems;
   }

   /**
    * Get moderator userID.
    *
    * @return int Moderator user ID.
    * @throws Gdn_UserException if cant determine moderator id
    */
   public function getModeratorUserID() {

      $userID = false;

      if ($this->moderatorUserID) {
         $userID = $this->moderatorUserID;
      }

      if (!$userID) {
         if (Gdn::Session()->CheckPermission('Garden.Moderation.Manage')) {
            return Gdn::Session()->UserID;
         }
      }

      if (!$userID) {
         throw new Gdn_UserException('Error finding Moderator');
      }


      return $userID;
   }

   /**
    * Sets the moderator.
    *
    * @param int $userID Moderator User ID.
    */
   public function setModerator($userID) {
      $this->moderatorUserID = $userID;
   }

   /**
    * Generate Foreign Id's
    *
    * @param null|array $data array of new data.
    * @param null|int $newID New ID for content.
    * @param null $contentType Content Type.
    * @return string ForeignID.
    * @throws Gdn_UserException Unknown content type.
    */
   public static function generateForeignID($data = null, $newID = null, $contentType = null) {

      if ($data == null && $newID != null) {
         switch (strtolower($contentType)) {
            case 'comment':
               return 'C-' . $newID;
               break;
            case 'discussion':
               return 'D-' . $newID;
               break;
            case 'activity':
               return 'A-' . $newID['ActivityID'];
               break;
            case 'activitycomment':
               return 'AC-' . $newID;
               break;
            default:
               throw new Gdn_UserException('Unknown content type');

         }
         return;
      }
      if (GetValue('CommentID', $data)) {
         //comment
         return 'C-' . $data['CommentID'];
      }
      if (GetValue('DiscussionID', $data)) {
         //discussion
         return 'D-' . $data['DiscussionID'];
      }
      if (GetValue('ActivityID', $data)) {
         //activity comment
         return 'AC-' . $data['ActivityID'];
      }

      return uniqid('', true);


   }

   public function validate($FormPostValues, $Insert = FALSE) {

      if (!$Insert) {
         if (GetValue('Status', $FormPostValues)) {
            //status update.  Check DateStatus + StatusUserID
            if (!GetValue('DateStatus', $FormPostValues) && !GetValue('StatusUserID', $FormPostValues)) {
               $this->Validation->AddValidationResult('Status', 'You must update required fields.' .
                  ' StatusUserID and DateStatus must be updated with status.');
            }
         }
      }
      return parent::Validate($FormPostValues, $Insert);

   }

   /**
    * Content Reporting.
    *
    * @param string $contentType
    * @param int $contentID
    * @param array $report
    *    [ReportUserID]
    *    [Reason]
    * @param string $queue
    * @throws Gdn_UserException
    * @return bool
    */
   public function report($contentType, $contentID, $report, $queue = 'reported') {

      $numberOfReportsToRemove = 2;
      $removeContent = false;

      switch (strtolower($contentType)) {

         case 'discussion':
            $model = new DiscussionModel();
            break;
         case 'comment':
            $model = new CommentModel();
            break;
         default:
            throw new Gdn_UserException('Unknown type');

      }

      $data = $model->GetID($contentID);
      if (!$data) {
         throw new Gdn_UserException($contentType . ' not found.', 404);
      }
      $data = (array)$data;

      $moderation = GetValueR('Attributes.Moderation', $data);
      if ($moderation) {
         Trace('Item has already been moderated.');
         return false;
      }

      $existingQueueRow = $this->GetWhere(
         array(
            'ForeignID' => $this->generateForeignID(null, $contentID, $contentType),
            'ForeignType' => $contentType
         ))
         ->FirstRow(DATASET_TYPE_ARRAY);


      if ($existingQueueRow) {
         // Check if content should be removed
         if (GetValue('Reports', $existingQueueRow)) {


            if (count($existingQueueRow['Reports']) >= $numberOfReportsToRemove) {
               $removeContent = true;
            }
         }
         // Save report to queue
         $newQueueRow = $existingQueueRow;
         if (GetValue('Reports', $existingQueueRow)) {
            $newReports[] = $existingQueueRow['Reports'];
         }
         $newReports[] = array(
            'ReportUserID' => $report['ReportUserID'],
            'DateReport' => Gdn_Format::ToDateTime(),
            'Reason' => GetValue('Reason', $report, NULL)
         );

         $newQueueRow['Reports'] = $newReports;
         $queueID = $this->Save($newQueueRow);

      } else {

         // Save to queue

         $data['Queue'] = $queue;
         $data['ForeignID'] = $contentID;
         $data['ForeignID'] = $this->generateForeignID(null, $contentID, $contentType);

         $queueRow = $this->convertToQueueRow($contentType, $data);

         switch (strtolower($contentType)) {
            case 'discussion':
               // Save ID so we can restore it to the same if approved
               $queueRow['DiscussionID'] = $data['DiscussionID'];
               break;
            case 'comment':
               // Save ID so we can restore it to the same if approved
               $queueRow['CommentID'] = $data['CommentID'];
               break;
            default:
               throw new Gdn_UserException('Unknown type');

         }

         $queueRow['InsertUserID'] = GetValue('ReportUserID', $report, Gdn::Session()->UserID);
         $queueRow['Reports'] = array(
            'ReportUserID' => $report['ReportUserID'],
            'DateReport' => Gdn_Format::ToDateTime(),
            'Reason' => GetValue('Reason', $report, NULL)
         );

         $queueID = $this->Save($queueRow);
         if ($numberOfReportsToRemove <= 1) {
            $removeContent = true;
         }

      }


      if ($removeContent) {

         $existingQueueRow = $this->GetID($queueID);

         $this->EventArguments['ReportHandled'] = false;
         $this->EventArguments['ForeignID'] = false;
         $this->EventArguments['QueueRow'] = $existingQueueRow;

         $this->FireEvent('ReportRemoval');
         Trace($this->EventArguments);

         // Update ForeignID in queue.
         if (!$this->EventArguments['ReportHandled']) {
            Trace('Failed getting ForeignID from plugins.');
            return;
         }
         // Update ForeignID in queue.
         if ($this->EventArguments['ForeignID']) {
            $existingQueueRow['ForeignID'] = $this->EventArguments['ForeignID'];
            $this->Save($existingQueueRow);
         }

         $this->removeContentIfRequired($existingQueueRow);
      }

      return $queueID;
   }


   /**
    * Handle content removal.
    *
    * @param $existingQueueRow
    * @throws Gdn_UserException
    */
   public function removeContentIfRequired($existingQueueRow) {

      switch (strtolower($existingQueueRow['ForeignType'])) {

         case 'discussion':
            // Content Removal.
            $model = new DiscussionModel();
            $model->Delete($existingQueueRow['DiscussionID']);
            break;
         case 'comment':
            // Content Removal.
            $model = new CommentModel();
            $model->Delete($existingQueueRow['CommentID']);
            break;
         default:
            throw new Gdn_UserException('Unknown type');

      }

   }

}