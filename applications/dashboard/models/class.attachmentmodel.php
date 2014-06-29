<?php
/**
 * @copyright 2009-2014 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 */
/**
 * Attachment Model.
 *
 * Attachments can be made to the following content Types
 *    - Discussion
 *    - Comment
 *
 * Attachment Fields
 *
 *    [AttachmentID]       - int       - Primary Key
 *    [ForeignID]          - string    - @see AttachmentModel::RowID()
 *    [ForeignUserID]      - int       - WIll be used for attachments on User (currently not supported)
 *    [Source]             - string    - PluginID ie 'salesforce', 'zendesk'
 *    [SourceID]           - string    - ie 123, abc123
 *    [Attributes]         - text      - This should not be used manually - see notes below about Attributes
 *    [DateInserted]       - datetime  - Added automatically
 *    [InsertUserID]       - int       - Added automatically
 *    [InsertIPAddress]    - string    - Added automatically
 *    [DateUpdated]        - datetime  - Added automatically
 *    [UpdateUserID]       - int       - Added automatically
 *    [UpdateIPAddress]    - string    - Added automatically
 *
 * Attributes
 *
 * If fields are passed that are not present in the database they will be added as 'Attributes'.  Attributes are stored
 * in the table in the field 'Attributes' the data is serialized.  When calling save an upsert is done; Meaning we will
 * update existing data.  If new Attributes are present they will be added; but the missing ones will not be removed.
 * In order to remove an attribute; you must pass NULL as the value.
 *
 * Views
 *
 * To customize attachment view you must catch the event 'FetchAttachmentViews' and include your own
 * functions to display attachment.
 * The method used to call the attachment is based on type,  The -'s are removed and the first letter of
 * each word capitalized,'
 *  Example:
 *   Add to Plugin:
 *    public function DiscussionController_FetchAttachmentViews_Handler($Sender) {
 *       require_once $Sender->FetchViewLocation('attachment', '', 'plugins/PluginID');
 *    }
 *  Now inside plugins views we add attachment.php with a function like:
 *    function WriteMyTypeAttachment($attachment) {}     ... would be called if Type = my-type
 *  You can see a skeleton for this in ./applications/dashboard/views/attachments/attachment.php
 *  function: WriteSkeletonAttachment
 *
 *
 * Errors
 *
 * To set an error just use the key 'Error'.  WriteErrorAttachment() will then display the error.
 */
class AttachmentModel extends Gdn_Model {
   /// Properties ///

   /**
    * @var AttachmentModel
    */
   static $Instance = NULL;

   /// Methods ///

   public function __construct() {
      parent::__construct('Attachment');
      $this->PrimaryKey = 'AttachmentID';
   }

   /**
    * Calculate any necessary values on an attachment row after it comes from the database.
    * @param array $Row The attachment row.
    */
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

      $InsertUser = Gdn::UserModel()->GetID($Row['InsertUserID']);
      $Row['InsertUser'] = array(
         'Name' => $InsertUser->Name,
         'ProfileLink' => UserAnchor($InsertUser)
      );
   }

   public function Enabled() {
      return C('Garden.AttachmentsEnabled', FALSE);
   }

   /**
    * Gather all of the ids from a dataset to get it ready to join attachments in.
    *
    * <code>
    * <?php
    * $ForeignIDs = array();
    * AttachmentModel::GatherIDs($Discussion, $ForeignIDs);
    * AttachmentModel::GatherIDs($Comments, $ForeignIDs);
    * $Attachments = $AttachmentModel->GetWhere(['ForeignID' => array_keys($ForeignIDs)]);
    * ?>
    * </code>
    *
    * @param array $Dataset
    * @param array $IDs
    */
   public static function GatherIDs($Dataset, &$IDs = array()) {
      if ((is_array($Dataset) && isset($Dataset[0])) || $Dataset instanceof Gdn_DataSet)  {
         foreach ($Dataset as $Row) {
            $id = self::RowID($Row);
            $IDs[$id] = $id;
         }
      } else {
         $id = self::RowID($Dataset);
         $IDs[$id] = $id;
      }
   }


   /**
    * Return the ForeignID based on the Row (Content Type)
    *
    * @param array|object $Row
    * @return string $ForeignId
    * @throws Gdn_UserException
    */
   public static function RowID($Row) {
      if ($id = val('CommentID', $Row)) {
         return 'c-'.$id;
      } elseif ($id = val('DiscussionID', $Row)) {
         return 'd-'.$id;
      } elseif ($id = val('UserID', $Row)) {
         return 'u-'.$id;
      }
      throw new Gdn_UserException('Failed to get Type...');

   }

   /**
    * {@inheritDoc}
    * in addition; We CalculateRow on the record found (Add Attachments)
    * @see Gdn_Model::GetID
    */

   public function GetID($ID, $DatasetType = DATASET_TYPE_ARRAY, $Options = array()) {
      $DatasetType = DATASET_TYPE_ARRAY;

      $Row = (array) parent::GetID($ID, $DatasetType, $Options);
      $this->CalculateRow($Row);
      return $Row;
   }

   /**
    * {@inheritDoc}
    * in addition; We CalculateRow for each record found (Add Attachments)
    * @see Gdn_Model::GetWhere
    */
   public function GetWhere($Where = FALSE, $OrderFields = '', $OrderDirection = 'asc', $Limit = FALSE, $Offset = FALSE) {

      $Data = parent::GetWhere($Where, $OrderFields, $OrderDirection, $Limit, $Offset);
      $Rows =& $Data->ResultArray();
      foreach ($Rows as &$Row) {
         $this->CalculateRow($Row);
      }

      return $Data;
   }

   /**
    * Return the singleton instance of this class.
    * @return AttachmentModel
    */
   public static function Instance() {
      if (!isset(self::$Instance)) {
         self::$Instance = new AttachmentModel();
      }
      return self::$Instance;
   }

   /**
    * Joins attachments to data
    *
    * <code>
    * <?php
    * $AttachmentModel->JoinAttachments($Discussion, $Comments);
    * ?>
    * </code>
    *
    * @param $Data - Data to which to attach comments
    * @param $Data2 - Optional set of Data to which to attach comments
    *
    */
   public function JoinAttachments(&$Data, &$Data2 = NULL) {
      if ($Data == NULL) {
         return;
      }
      // Gather the Ids.
      $ForeignIDs = array();
      self::GatherIDs($Data, $ForeignIDs);
      if ($Data2)
         self::GatherIDs($Data2, $ForeignIDs);
      // Get the attachments.
      $Attachments = $this->GetWhere(array('ForeignID' => array_keys($ForeignIDs)), 'DateInserted', 'desc')->ResultArray();
      $Attachments = Gdn_DataSet::Index($Attachments, 'ForeignID', array('Unique' => FALSE));

      // Join the attachments.
      $this->JoinAttachmentsTo($Data, $Attachments);
      if ($Data2) {
         $this->JoinAttachmentsTo($Data2, $Attachments);
      }
   }

   /**
    * @param ProfileController $Sender
    * @param array $Args
    * @param array $Where
    * @param int $Limit
    * @return bool
    */
   public function JoinAttachmentsToUser($Sender, $Args, $Where = array(), $Limit = 20) {
      $User = $Sender->User;
      if (!is_object($User)) {
         return FALSE;
      }
      $Where = array_merge(array('ForeignUserID' => $User->UserID), $Where);

      $Attachments = $this->GetWhere($Where, '', 'desc', $Limit)->ResultArray();
      $Sender->SetData('Attachments',  $Attachments);
      return TRUE;
   }

   protected function JoinAttachmentsTo(&$Data, $Attachments) {
      if (is_a($Data, 'Gdn_DataSet') || (is_array($Data) && isset($Data[0]))) {
         // This is a dataset.
         foreach ($Data as &$Row) {
            // This is a single record.
            $RowID = self::RowID($Row);
            if (isset($Attachments[$RowID])) {
               SetValue('Attachments', $Row, $Attachments[$RowID]);
            }
         }
      } else {
         // This is a single record.
         $RowID = self::RowID($Data);
         if (isset($Attachments[$RowID])) {
            SetValue('Attachments', $Data, $Attachments[$RowID]);
         }
      }
   }

   /**
    * {@inheritDoc}
    * @param array $Data
    * @param bool $Settings
    * @return bool|mixed Primary Key Value
    */
   public function Save($Data, $Settings = FALSE) {
      $this->DefineSchema();
      $SchemaFields = $this->Schema->Fields();

      $SaveData = array();
      $Attributes = array();

      // Grab the current attachment.
      if (isset($Data['AttachmentID'])) {
         $PrimaryKeyVal = $Data['AttachmentID'];
         $CurrentAttachment = $this->SQL->GetWhere('Attachment', array('AttachmentID' => $PrimaryKeyVal))->FirstRow(DATASET_TYPE_ARRAY);
         if ($CurrentAttachment) {

            $Attributes = @unserialize($CurrentAttachment['Attributes']);
            if (!$Attributes)
               $Attributes = array();

            $Insert = FALSE;
         } else
            $Insert = TRUE;
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

   /**
    * Given an attachment type, returns the view function to display attachment.
    *
    * @param string $Type Attachment type.
    * @return string Function name.
    */
   public static function GetWriteAttachmentMethodName($Type) {
      $method = str_replace('-', ' ', $Type);
      $method = ucwords($method);
      $method = str_replace(' ', '', $method);
      return 'Write' . $method . 'Attachment';
   }

}
