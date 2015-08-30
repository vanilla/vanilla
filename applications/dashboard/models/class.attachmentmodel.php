<?php
/**
 * Attachment Model.
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Dashboard
 * @since 2.2
 */

/**
 * Handles attachments. Least-Buddhist model of them all.
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
 *    public function discussionController_FetchAttachmentViews_Handler($Sender) {
 *       require_once $Sender->fetchViewLocation('attachment', '', 'plugins/PluginID');
 *    }
 *  Now inside plugins views we add attachment.php with a function like:
 *    function writeMyTypeAttachment($attachment) {}     ... would be called if Type = my-type
 *  You can see a skeleton for this in ./applications/dashboard/views/attachments/attachment.php
 *  function: WriteSkeletonAttachment
 *
 *
 * Errors
 *
 * To set an error just use the key 'Error'.  WriteErrorAttachment() will then display the error.
 */
class AttachmentModel extends Gdn_Model {

    /** @var AttachmentModel */
    static $Instance = null;

    /**
     * Set up the attachment.
     */
    public function __construct() {
        parent::__construct('Attachment');
        $this->PrimaryKey = 'AttachmentID';
    }

    /**
     * Calculate any necessary values on an attachment row after it comes from the database.
     *
     * @param array $Row The attachment row.
     */
    protected function calculateRow(&$Row) {
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

        $InsertUser = Gdn::userModel()->getID($Row['InsertUserID']);
        $Row['InsertUser'] = array(
            'Name' => $InsertUser->Name,
            'ProfileLink' => userAnchor($InsertUser)
        );
    }

    /**
     * Whether attachments are enabled.
     *
     * @return bool
     */
    public static function enabled() {
        return c('Garden.AttachmentsEnabled', false);
    }

    /**
     * Gather all of the ids from a dataset to get it ready to join attachments in.
     *
     * <code>
     * <?php
     * $ForeignIDs = array();
     * AttachmentModel::GatherIDs($Discussion, $ForeignIDs);
     * AttachmentModel::GatherIDs($Comments, $ForeignIDs);
     * $Attachments = $AttachmentModel->getWhere(['ForeignID' => array_keys($ForeignIDs)]);
     * ?>
     * </code>
     *
     * @param array $Dataset
     * @param array $IDs
     */
    public static function gatherIDs($Dataset, &$IDs = array()) {
        if ((is_array($Dataset) && isset($Dataset[0])) || $Dataset instanceof Gdn_DataSet) {
            foreach ($Dataset as $Row) {
                $id = self::rowID($Row);
                $IDs[$id] = $id;
            }
        } else {
            $id = self::rowID($Dataset);
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
    public static function rowID($Row) {
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

    public function getID($ID, $DatasetType = DATASET_TYPE_ARRAY, $Options = array()) {
        $DatasetType = DATASET_TYPE_ARRAY;

        $Row = (array)parent::getID($ID, $DatasetType, $Options);
        $this->calculateRow($Row);
        return $Row;
    }

    /**
     * {@inheritDoc}
     * in addition; We CalculateRow for each record found (Add Attachments)
     * @see Gdn_Model::GetWhere
     */
    public function getWhere($Where = false, $OrderFields = '', $OrderDirection = 'asc', $Limit = false, $Offset = false) {

        $Data = parent::getWhere($Where, $OrderFields, $OrderDirection, $Limit, $Offset);
        $Rows =& $Data->resultArray();
        foreach ($Rows as &$Row) {
            $this->calculateRow($Row);
        }

        return $Data;
    }

    /**
     * Return the singleton instance of this class.
     * @return AttachmentModel
     */
    public static function instance() {
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
    public function joinAttachments(&$Data, &$Data2 = null) {
        if ($Data == null) {
            return;
        }
        // Gather the Ids.
        $ForeignIDs = array();
        self::gatherIDs($Data, $ForeignIDs);
        if ($Data2) {
            self::gatherIDs($Data2, $ForeignIDs);
        }
        // Get the attachments.
        $Attachments = $this->getWhere(array('ForeignID' => array_keys($ForeignIDs)), 'DateInserted', 'desc')->resultArray();
        $Attachments = Gdn_DataSet::index($Attachments, 'ForeignID', array('Unique' => false));

        // Join the attachments.
        $this->joinAttachmentsTo($Data, $Attachments);
        if ($Data2) {
            $this->joinAttachmentsTo($Data2, $Attachments);
        }
    }

    /**
     * @param ProfileController $Sender
     * @param array $Args
     * @param array $Where
     * @param int $Limit
     * @return bool
     */
    public function joinAttachmentsToUser($Sender, $Args, $Where = array(), $Limit = 20) {
        $User = $Sender->User;
        if (!is_object($User)) {
            return false;
        }
        $Where = array_merge(array('ForeignUserID' => $User->UserID), $Where);

        $Attachments = $this->getWhere($Where, '', 'desc', $Limit)->resultArray();
        $Sender->setData('Attachments', $Attachments);
        return true;
    }

    protected function joinAttachmentsTo(&$Data, $Attachments) {
        if (is_a($Data, 'Gdn_DataSet') || (is_array($Data) && isset($Data[0]))) {
            // This is a dataset.
            foreach ($Data as &$Row) {
                // This is a single record.
                $RowID = self::rowID($Row);
                if (isset($Attachments[$RowID])) {
                    setValue('Attachments', $Row, $Attachments[$RowID]);
                }
            }
        } else {
            // This is a single record.
            $RowID = self::rowID($Data);
            if (isset($Attachments[$RowID])) {
                setValue('Attachments', $Data, $Attachments[$RowID]);
            }
        }
    }

    /**
     * {@inheritDoc}
     * @param array $Data
     * @param bool $Settings
     * @return bool|mixed Primary Key Value
     */
    public function save($Data, $Settings = false) {
        $this->defineSchema();
        $SchemaFields = $this->Schema->fields();

        $SaveData = array();
        $Attributes = array();

        // Grab the current attachment.
        if (isset($Data['AttachmentID'])) {
            $PrimaryKeyVal = $Data['AttachmentID'];
            $CurrentAttachment = $this->SQL->getWhere('Attachment', array('AttachmentID' => $PrimaryKeyVal))->firstRow(DATASET_TYPE_ARRAY);
            if ($CurrentAttachment) {
                $Attributes = @unserialize($CurrentAttachment['Attributes']);
                if (!$Attributes) {
                    $Attributes = array();
                }

                $Insert = false;
            } else {
                $Insert = true;
            }
        } else {
            $PrimaryKeyVal = false;
            $Insert = true;
        }

        // Grab any values that aren't in the db schema and stick them in attributes.
        foreach ($Data as $Name => $Value) {
            if ($Name == 'Attributes') {
                continue;
            }
            if (isset($SchemaFields[$Name])) {
                $SaveData[$Name] = $Value;
            } elseif ($Value === null) {
                unset($Attributes[$Name]);
            } else {
                $Attributes[$Name] = $Value;
            }
        }
        if (sizeof($Attributes)) {
            $SaveData['Attributes'] = $Attributes;
        } else {
            $SaveData['Attributes'] = null;
        }

        if ($Insert) {
            $this->addInsertFields($SaveData);
        } else {
            $this->addUpdateFields($SaveData);
        }

        // Validate the form posted values.
        if ($this->validate($SaveData, $Insert) === true) {
            $Fields = $this->Validation->validationFields();

            if ($Insert === false) {
                $Fields = removeKeyFromArray($Fields, $this->PrimaryKey); // Don't try to update the primary key
                $this->update($Fields, array($this->PrimaryKey => $PrimaryKeyVal));
            } else {
                $PrimaryKeyVal = $this->insert($Fields);
            }
        } else {
            $PrimaryKeyVal = false;
        }
        return $PrimaryKeyVal;
    }

    /**
     * Given an attachment type, returns the view function to display attachment.
     *
     * @param string $Type Attachment type.
     * @return string Function name.
     */
    public static function getWriteAttachmentMethodName($Type) {
        $method = str_replace('-', ' ', $Type);
        $method = ucwords($method);
        $method = str_replace(' ', '', $method);
        return 'Write'.$method.'Attachment';
    }
}
