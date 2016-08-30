<?php
/**
 * Draft model
 *
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Vanilla
 * @since 2.0
 */

/**
 * Manages unpublished drafts of comments and discussions.
 */
class DraftModel extends VanillaModel {

    /**
     * Class constructor. Defines the related database table name.
     *
     * @since 2.0.0
     * @access public
     */
    public function __construct() {
        parent::__construct('Draft');
    }

    /**
     * Build base SQL query used by get methods.
     *
     * @since 2.0.0
     * @access public
     */
    public function draftQuery() {
        $this->SQL
            ->select('d.*')
            ->from('Draft d');
    }

    /**
     * {@inheritdoc}
     */
    public function get($OrderFields = '', $OrderDirection = 'asc', $Limit = false, $PageNumber = false) {
        if (is_numeric($OrderFields)) {
            deprecated('DraftModel->get()', 'DraftModel->getByUser()');
            return $this->getByUser($OrderFields, $OrderDirection, $Limit, $PageNumber);
        }
    }

    /**
     * Get drafts matching a given criteria.
     *
     * @param int $UserID Unique ID of user that wrote the drafts.
     * @param int $Offset Number of results to skip.
     * @param int $Limit Max number of drafts to return.
     * @param int $DiscussionID Limits drafts returned to a single discussion.
     * @return object Gdn_DataSet SQL results.
     */
    public function getByUser($UserID, $Offset = '0', $Limit = '', $DiscussionID = '') {
        if (!is_numeric($Offset) || $Offset < 0) {
            $Offset = 0;
        }

        if (!is_numeric($Limit) || $Limit < 1) {
            $Limit = 100;
        }

        $this->draftQuery();
        $this->SQL
            ->select('d.Name, di.Name', 'coalesce', 'Name')
            ->select('di.DateInserted', '', 'DiscussionExists')
            ->join('Discussion di', 'd.discussionID = di.DiscussionID', 'left')
            ->where('d.InsertUserID', $UserID)
            ->orderBy('d.DateInserted', 'desc')
            ->limit($Limit, $Offset);

        if (is_numeric($DiscussionID) && $DiscussionID > 0) {
            $this->SQL->where('d.DiscussionID', $DiscussionID);
        }

        return $this->SQL->get();
    }

    /**
     * Gets data for a single draft.
     *
     * @param int $draftID Unique ID of draft to get data for.
     * @param string|false $dataSetType The format of the data.
     * @param array $options Not used.
     * @return object SQL results.
     */
    public function getID($draftID, $dataSetType = false, $options = []) {
        $dataSetType = $dataSetType ?: DATASET_TYPE_OBJECT;

        $this->draftQuery();
        return $this->SQL
            ->where('d.DraftID', $draftID)
            ->get()
            ->firstRow($dataSetType);
    }

    /**
     * {@inheritdoc}
     */
    public function getCount($Wheres = '') {
        if (is_numeric($Wheres)) {
            deprecated('DraftModel->getCount(int)', 'DraftModel->getCountByUser()');
            return $this->getCountByUser($Wheres);
        }

        return parent::getCount($Wheres);
    }

    /**
     * Gets number of drafts a user has.
     *
     * @param int $UserID Unique ID of user to count drafts for.
     * @return int Total drafts.
     */
    public function getCountByUser($UserID) {
        return $this->SQL
            ->select('DraftID', 'count', 'CountDrafts')
            ->from('Draft')
            ->where('InsertUserID', $UserID)
            ->get()
            ->firstRow()
            ->CountDrafts;
    }

    /**
     * Insert or update a draft from form values.
     *
     * @param array $formPostValues Form values sent from form model.
     * @param array $settings Not used.
     * @return int Unique ID of draft.
     */
    public function save($formPostValues, $settings = []) {
        $Session = Gdn::session();

        // Define the primary key in this model's table.
        $this->defineSchema();

        // Add & apply any extra validation rules:
        $this->Validation->applyRule('Body', 'Required');
        $MaxCommentLength = Gdn::config('Vanilla.Comment.MaxLength');
        if (is_numeric($MaxCommentLength) && $MaxCommentLength > 0) {
            $this->Validation->setSchemaProperty('Body', 'Length', $MaxCommentLength);
            $this->Validation->applyRule('Body', 'Length');
        }

        // Get the DraftID from the form so we know if we are inserting or updating.
        $DraftID = val('DraftID', $formPostValues, '');
        $Insert = $DraftID == '' ? true : false;

        if (!$DraftID) {
            unset($formPostValues['DraftID']);
        }

        // Remove the discussionid from the form value collection if it's empty
        if (array_key_exists('DiscussionID', $formPostValues) && $formPostValues['DiscussionID'] == '') {
            unset($formPostValues['DiscussionID']);
        }

        if ($Insert) {
            // If no categoryid is defined, grab the first available.
            if (val('CategoryID', $formPostValues) === false) {
                $formPostValues['CategoryID'] = $this->SQL->get('Category', '', '', 1)->firstRow()->CategoryID;
            }

        }
        // Add the update fields because this table's default sort is by DateUpdated (see $this->get()).
        $this->addInsertFields($formPostValues);
        $this->addUpdateFields($formPostValues);

        // Remove checkboxes from the fields if they were unchecked
        if (val('Announce', $formPostValues, '') === false) {
            unset($formPostValues['Announce']);
        }

        if (val('Closed', $formPostValues, '') === false) {
            unset($formPostValues['Closed']);
        }

        if (val('Sink', $formPostValues, '') === false) {
            unset($formPostValues['Sink']);
        }

        // Validate the form posted values
        if ($this->validate($formPostValues, $Insert)) {
            $Fields = $this->Validation->schemaValidationFields(); // All fields on the form that relate to the schema
            $DraftID = intval(val('DraftID', $Fields, 0));

            // If the post is new and it validates, make sure the user isn't spamming
            if ($DraftID > 0) {
                // Update the draft.
                unset($Fields['DraftID']); // remove the primary key from the fields for saving
                $this->SQL->put($this->Name, $Fields, array($this->PrimaryKey => $DraftID));
            } else {
                // Insert the draft
                unset($Fields['DraftID']);
                $DraftID = $this->SQL->insert($this->Name, $Fields);
                $this->UpdateUser($Session->UserID);
            }
        }

        return $DraftID;
    }

    /**
     * Delete a draft.
     *
     * {@inheritdoc}
     */
    public function delete($where = [], $options = []) {
        if (is_numeric($where)) {
            deprecated('DraftModel->delete(int)', 'DraftModel->deleteID(int)');

            $result = $this->deleteID($where, $options);
            return $result;
        }

        throw new \BadMethodCallException("DraftModel->delete() is not supported.", 400);
    }

    /**
     * Deletes a specified draft.
     *
     * This is a hard delete that completely removes it.
     *
     * @param int $draftID Unique ID of the draft to be deleted.
     * @param array $options Not used.
     * @return bool Always returns TRUE.
     */
    public function deleteID($draftID, $options = []) {
        // Get some information about this draft
        $DraftUser = $this->SQL
            ->select('InsertUserID')
            ->from('Draft')
            ->where('DraftID', $draftID)
            ->get()
            ->firstRow();

        $this->SQL->delete('Draft', array('DraftID' => $draftID));
        if (is_object($DraftUser)) {
            $this->updateUser($DraftUser->InsertUserID);
        }

        return true;
    }

    /**
     * Updates a user's draft count.
     *
     * @since 2.0.0
     * @access public
     *
     * @param int $UserID Unique ID of the user to be updated.
     */
    public function updateUser($UserID) {
        // Retrieve a draft count
        $CountDrafts = $this->getCount($UserID);

        // Update CountDrafts column of user table fot this user
        Gdn::userModel()->setField($UserID, 'CountDrafts', $CountDrafts);
    }
}
