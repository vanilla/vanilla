<?php
/**
 * Draft model
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Vanilla
 * @since 2.0
 */

/**
 * Manages unpublished drafts of comments and discussions.
 */
class DraftModel extends Gdn_Model {

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
    public function get($orderFields = '', $orderDirection = 'asc', $limit = false, $pageNumber = false) {
        if (is_numeric($orderFields)) {
            deprecated('DraftModel->get()', 'DraftModel->getByUser()');
            return $this->getByUser($orderFields, $orderDirection, $limit, $pageNumber);
        }
    }

    /**
     * Get drafts matching a given criteria.
     *
     * @param int $userID Unique ID of user that wrote the drafts.
     * @param int $offset Number of results to skip.
     * @param int $limit Max number of drafts to return.
     * @param int $discussionID Limits drafts returned to a single discussion.
     * @return object Gdn_DataSet SQL results.
     */
    public function getByUser($userID, $offset = '0', $limit = '', $discussionID = '') {
        if (!is_numeric($offset) || $offset < 0) {
            $offset = 0;
        }

        if (!is_numeric($limit) || $limit < 1) {
            $limit = 100;
        }

        $this->draftQuery();
        $this->SQL
            ->select('d.Name, di.Name', 'coalesce', 'Name')
            ->select('di.DateInserted', '', 'DiscussionExists')
            ->join('Discussion di', 'd.discussionID = di.DiscussionID', 'left')
            ->where('d.InsertUserID', $userID)
            ->orderBy('d.DateInserted', 'desc')
            ->limit($limit, $offset);

        if (is_numeric($discussionID) && $discussionID > 0) {
            $this->SQL->where('d.DiscussionID', $discussionID);
        }

        return $this->SQL->get();
    }

    /**
     * Gets data for a single draft.
     *
     * @param int $draftID Unique ID of draft to get data for.
     * @param string|false $dataSetType The format of the data.
     * @param array $options Not used.
     * @return array|object SQL results.
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
    public function getCount($wheres = '') {
        if (is_numeric($wheres)) {
            deprecated('DraftModel->getCount(int)', 'DraftModel->getCountByUser()');
            return $this->getCountByUser($wheres);
        }

        return parent::getCount($wheres);
    }

    /**
     * Gets number of drafts a user has.
     *
     * @param int $userID Unique ID of user to count drafts for.
     * @return int Total drafts.
     */
    public function getCountByUser($userID) {
        return $this->SQL
            ->select('DraftID', 'count', 'CountDrafts')
            ->from('Draft')
            ->where('InsertUserID', $userID)
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
        $session = Gdn::session();

        // Define the primary key in this model's table.
        $this->defineSchema();

        if (array_key_exists('Body', $formPostValues)) {
            // Add & apply any extra validation rules:
            $this->Validation->applyRule('Body', 'Required');
            $maxCommentLength = Gdn::config('Vanilla.Comment.MaxLength');
            if (is_numeric($maxCommentLength) && $maxCommentLength > 0) {
                $this->Validation->setSchemaProperty('Body', 'Length', $maxCommentLength);
                $this->Validation->applyRule('Body', 'Length');
            }
        }

        // Get the DraftID from the form so we know if we are inserting or updating.
        $draftID = (int) val('DraftID', $formPostValues, 0);
        $insert = $draftID === 0 ? true : false;

        if (!$draftID) {
            unset($formPostValues['DraftID']);
        }

        // Remove the discussionid from the form value collection if it's empty
        if (array_key_exists('DiscussionID', $formPostValues) && $formPostValues['DiscussionID'] === '') {
            unset($formPostValues['DiscussionID']);
        }

        if (array_key_exists('CategoryID', $formPostValues) && filter_var($formPostValues['CategoryID'], FILTER_VALIDATE_INT) === false) {
            unset($formPostValues['CategoryID']);
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
        if ($this->validate($formPostValues, $insert)) {
            $fields = $this->Validation->schemaValidationFields(); // All fields on the form that relate to the schema
            $draftID = intval(val('DraftID', $fields, 0));

            // If the post is new and it validates, make sure the user isn't spamming
            if ($draftID > 0) {
                // Update the draft.
                unset($fields['DraftID']); // remove the primary key from the fields for saving
                $this->SQL->put($this->Name, $fields, [$this->PrimaryKey => $draftID]);
            } else {
                // Insert the draft
                unset($fields['DraftID']);
                $draftID = $this->SQL->insert($this->Name, $fields);
                $this->updateUser($session->UserID);
            }
        }

        return $draftID;
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
        $draftUser = $this->SQL
            ->select('InsertUserID')
            ->from('Draft')
            ->where('DraftID', $draftID)
            ->get()
            ->firstRow();

        $this->SQL->delete('Draft', ['DraftID' => $draftID]);
        if (is_object($draftUser)) {
            $this->updateUser($draftUser->InsertUserID);
        }

        return true;
    }

    /**
     * Updates a user's draft count.
     *
     * @since 2.0.0
     * @access public
     *
     * @param int $userID Unique ID of the user to be updated.
     */
    public function updateUser($userID) {
        // Retrieve a draft count
        $countDrafts = $this->getCountByUser($userID);

        // Update CountDrafts column of user table fot this user
        Gdn::userModel()->setField($userID, 'CountDrafts', $countDrafts);
    }
}
