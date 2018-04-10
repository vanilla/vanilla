<?php
/**
 * Attachment Model.
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
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
 *    [ForeignID]          - string    - @see AttachmentModel::rowID()
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
 * To set an error just use the key 'Error'.  writeErrorAttachment() will then display the error.
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
     * @param array $row The attachment row.
     */
    protected function calculateRow(&$row) {
        if (isset($row['Attributes']) && !empty($row['Attributes'])) {
            if (is_array($row['Attributes'])) {
                $attributes = $row['Attributes'];
            } else {
                $attributes = dbdecode($row['Attributes']);
            }
            if (is_array($attributes)) {
                $row = array_replace($row, $attributes);
            }
        }
        unset($row['Attributes']);

        $insertUser = Gdn::userModel()->getID($row['InsertUserID']);
        $row['InsertUser'] = [
            'Name' => $insertUser->Name,
            'ProfileLink' => userAnchor($insertUser)
        ];
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
     * AttachmentModel::gatherIDs($Discussion, $ForeignIDs);
     * AttachmentModel::gatherIDs($Comments, $ForeignIDs);
     * $Attachments = $AttachmentModel->getWhere(['ForeignID' => array_keys($ForeignIDs)]);
     * ?>
     * </code>
     *
     * @param array $dataset
     * @param array $iDs
     */
    public static function gatherIDs($dataset, &$iDs = []) {
        if ((is_array($dataset) && isset($dataset[0])) || $dataset instanceof Gdn_DataSet) {
            foreach ($dataset as $row) {
                $id = self::rowID($row);
                $iDs[$id] = $id;
            }
        } else {
            $id = self::rowID($dataset);
            $iDs[$id] = $id;
        }
    }


    /**
     * Return the ForeignID based on the Row (Content Type)
     *
     * @param array|object $row
     * @return string $ForeignId
     * @throws Gdn_UserException
     */
    public static function rowID($row) {
        if ($id = val('CommentID', $row)) {
            return 'c-'.$id;
        } elseif ($id = val('DiscussionID', $row)) {
            return 'd-'.$id;
        } elseif ($id = val('UserID', $row)) {
            return 'u-'.$id;
        }
        throw new Gdn_UserException('Failed to get Type...');

    }

    /**
     * {@inheritDoc}
     * in addition; We CalculateRow on the record found (Add Attachments)
     * @see Gdn_Model::GetID
     */

    public function getID($iD, $datasetType = DATASET_TYPE_ARRAY, $options = []) {
        $datasetType = DATASET_TYPE_ARRAY;

        $row = (array)parent::getID($iD, $datasetType, $options);
        $this->calculateRow($row);
        return $row;
    }

    /**
     * {@inheritDoc}
     * in addition; We CalculateRow for each record found (Add Attachments)
     * @see Gdn_Model::GetWhere
     */
    public function getWhere($where = false, $orderFields = '', $orderDirection = 'asc', $limit = false, $offset = false) {

        $data = parent::getWhere($where, $orderFields, $orderDirection, $limit, $offset);
        $rows =& $data->resultArray();
        foreach ($rows as &$row) {
            $this->calculateRow($row);
        }

        return $data;
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
     * $AttachmentModel->joinAttachments($Discussion, $Comments);
     * ?>
     * </code>
     *
     * @param $data - Data to which to attach comments
     * @param $data2 - Optional set of Data to which to attach comments
     *
     */
    public function joinAttachments(&$data, &$data2 = null) {
        if ($data == null) {
            return;
        }
        // Gather the Ids.
        $foreignIDs = [];
        self::gatherIDs($data, $foreignIDs);
        if ($data2) {
            self::gatherIDs($data2, $foreignIDs);
        }
        // Get the attachments.
        $attachments = $this->getWhere(['ForeignID' => array_keys($foreignIDs)], 'DateInserted', 'desc')->resultArray();
        $attachments = Gdn_DataSet::index($attachments, 'ForeignID', ['Unique' => false]);

        // Join the attachments.
        $this->joinAttachmentsTo($data, $attachments);
        if ($data2) {
            $this->joinAttachmentsTo($data2, $attachments);
        }
    }

    /**
     * @param ProfileController $sender
     * @param array $args
     * @param array $where
     * @param int $limit
     * @return bool
     */
    public function joinAttachmentsToUser($sender, $args, $where = [], $limit = 20) {
        $user = $sender->User;
        if (!is_object($user)) {
            return false;
        }
        $where = array_merge(['ForeignUserID' => $user->UserID], $where);

        $attachments = $this->getWhere($where, '', 'desc', $limit)->resultArray();
        $sender->setData('Attachments', $attachments);
        return true;
    }

    protected function joinAttachmentsTo(&$data, $attachments) {
        if (is_a($data, 'Gdn_DataSet') || (is_array($data) && isset($data[0]))) {
            // This is a dataset.
            foreach ($data as &$row) {
                // This is a single record.
                $rowID = self::rowID($row);
                if (isset($attachments[$rowID])) {
                    setValue('Attachments', $row, $attachments[$rowID]);
                }
            }
        } else {
            // This is a single record.
            $rowID = self::rowID($data);
            if (isset($attachments[$rowID])) {
                setValue('Attachments', $data, $attachments[$rowID]);
            }
        }
    }

    /**
     * {@inheritDoc}
     * @param array $data
     * @param bool $settings
     * @return bool|mixed Primary Key Value
     */
    public function save($data, $settings = false) {
        $this->defineSchema();
        $schemaFields = $this->Schema->fields();

        $saveData = [];
        $attributes = [];

        // Grab the current attachment.
        if (isset($data['AttachmentID'])) {
            $primaryKeyVal = $data['AttachmentID'];
            $currentAttachment = $this->SQL->getWhere('Attachment', ['AttachmentID' => $primaryKeyVal])->firstRow(DATASET_TYPE_ARRAY);
            if ($currentAttachment) {
                $attributes = dbdecode($currentAttachment['Attributes']);
                if (!$attributes) {
                    $attributes = [];
                }

                $insert = false;
            } else {
                $insert = true;
            }
        } else {
            $primaryKeyVal = false;
            $insert = true;
        }

        // Grab any values that aren't in the db schema and stick them in attributes.
        foreach ($data as $name => $value) {
            if ($name == 'Attributes') {
                continue;
            }
            if (isset($schemaFields[$name])) {
                $saveData[$name] = $value;
            } elseif ($value === null) {
                unset($attributes[$name]);
            } else {
                $attributes[$name] = $value;
            }
        }
        if (sizeof($attributes)) {
            $saveData['Attributes'] = $attributes;
        } else {
            $saveData['Attributes'] = null;
        }

        if ($insert) {
            $this->addInsertFields($saveData);
        } else {
            $this->addUpdateFields($saveData);
        }

        // Validate the form posted values.
        if ($this->validate($saveData, $insert) === true) {
            $fields = $this->Validation->validationFields();

            if ($insert === false) {
                unset($fields[$this->PrimaryKey]); // Don't try to update the primary key
                $this->update($fields, [$this->PrimaryKey => $primaryKeyVal]);
            } else {
                $primaryKeyVal = $this->insert($fields);
            }
        } else {
            $primaryKeyVal = false;
        }
        return $primaryKeyVal;
    }

    /**
     * Given an attachment type, returns the view function to display attachment.
     *
     * @param string $type Attachment type.
     * @return string Function name.
     */
    public static function getWriteAttachmentMethodName($type) {
        $method = str_replace('-', ' ', $type);
        $method = ucwords($method);
        $method = str_replace(' ', '', $method);
        return 'Write'.$method.'Attachment';
    }
}
