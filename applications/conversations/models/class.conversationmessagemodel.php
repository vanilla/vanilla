<?php
/**
 * Conversation message model.
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Conversations
 * @since 2.0
 */

/**
 * Manages messages in a conversation.
 */
class ConversationMessageModel extends ConversationsModel {

    /**
     * Class constructor. Defines the related database table name.
     *
     * @since 2.0.0
     * @access public
     */
    public function __construct() {
        parent::__construct('ConversationMessage');
        $this->PrimaryKey = 'MessageID';
    }

    /**
     * Get messages by conversation.
     *
     * Events: BeforeGet.
     *
     * @since 2.0.0
     * @access public
     *
     * @param int $ConversationID Unique ID of conversation being viewed.
     * @param int $ViewingUserID Unique ID of current user.
     * @param int $Offset Number to skip.
     * @param int $Limit Maximum to return.
     * @param array $Wheres SQL conditions.
     * @return Gdn_DataSet SQL results.
     */
    public function get($ConversationID, $ViewingUserID, $Offset = '0', $Limit = '', $Wheres = '') {
        if ($Limit == '') {
            $Limit = Gdn::config('Conversations.Messages.PerPage', 50);
        }

        $Offset = !is_numeric($Offset) || $Offset < 0 ? 0 : $Offset;
        if (is_array($Wheres)) {
            $this->SQL->where($Wheres);
        }

        $this->fireEvent('BeforeGet');
        return $this->SQL
            ->select('cm.*')
            ->select('iu.Name', '', 'InsertName')
            ->select('iu.Email', '', 'InsertEmail')
            ->select('iu.Photo', '', 'InsertPhoto')
            ->from('ConversationMessage cm')
            ->join('Conversation c', 'cm.ConversationID = c.ConversationID')
            ->join('UserConversation uc', 'c.ConversationID = uc.ConversationID and uc.UserID = '.$ViewingUserID, 'left')
            ->join('User iu', 'cm.InsertUserID = iu.UserID', 'left')
            ->beginWhereGroup()
            ->where('uc.DateCleared is null')
            ->orWhere('uc.DateCleared <', 'cm.DateInserted', true, false) // Make sure that cleared conversations do not show up unless they have new messages added.
            ->endWhereGroup()
            ->where('cm.ConversationID', $ConversationID)
            ->orderBy('cm.DateInserted', 'asc')
            ->limit($Limit, $Offset)
            ->get();
    }

    /**
     * Get the data from the model based on its primary key.
     *
     * @param mixed $ID The value of the primary key in the database.
     * @param string $DatasetType The format of the result dataset.
     * @return Gdn_DataSet
     */
    public function getID($ID, $DatasetType = false) {
        $Result = $this->getWhere(array("MessageID" => $ID))->firstRow($DatasetType);
        return $Result;
    }

    /**
     * Get only new messages from conversation.
     *
     * @since 2.0.0
     * @access public
     *
     * @param int $ConversationID Unique ID of conversation being viewed.
     * @param int $LastMessageID Unique ID of last message to be viewed.
     * @return Gdn_DataSet SQL results.
     */
    public function getNew($ConversationID, $LastMessageID) {
        $Session = Gdn::session();
        $this->SQL->where('MessageID > ', $LastMessageID);
        return $this->get($ConversationID, $Session->UserID);
    }

    /**
     * Get number of messages in a conversation.
     *
     * @since 2.0.0
     * @access public
     *
     * @param int $ConversationID Unique ID of conversation being viewed.
     * @param int $ViewingUserID Unique ID of current user.
     * @param array $Wheres SQL conditions.
     * @return int Number of messages.
     */
    public function getCount($ConversationID, $ViewingUserID, $Wheres = '') {
        if (is_array($Wheres)) {
            $this->SQL->where($Wheres);
        }

        $Data = $this->SQL
            ->select('cm.MessageID', 'count', 'Count')
            ->from('ConversationMessage cm')
            ->join('Conversation c', 'cm.ConversationID = c.ConversationID')
            ->join('UserConversation uc', 'c.ConversationID = uc.ConversationID and uc.UserID = '.$ViewingUserID)
            ->beginWhereGroup()
            ->where('uc.DateCleared is null')
            ->orWhere('uc.DateCleared >', 'c.DateUpdated', true, false) // Make sure that cleared conversations do not show up unless they have new messages added.
            ->endWhereGroup()
            ->groupBy('cm.ConversationID')
            ->where('cm.ConversationID', $ConversationID)
            ->get();

        if ($Data->numRows() > 0) {
            return $Data->firstRow()->Count;
        }

        return 0;
    }

    /**
     * Get number of messages that meet criteria.
     *
     * @since 2.0.0
     * @access public
     *
     * @param array $Wheres SQL conditions.
     * @return int Number of messages.
     */
    public function getCountWhere($Wheres = '') {
        if (is_array($Wheres)) {
            $this->SQL->where($Wheres);
        }

        $Data = $this->SQL
            ->select('MessageID', 'count', 'Count')
            ->from('ConversationMessage')
            ->get();

        if ($Data->numRows() > 0) {
            return $Data->firstRow()->Count;
        }

        return 0;
    }

    /**
     * Save message from form submission.
     *
     * @since 2.0.0
     * @access public
     *
     * @param array $FormPostValues Values submitted via form.
     * @return int Unique ID of message created or updated.
     */
    public function save($FormPostValues, $Conversation = null, $Options = array()) {
        $Session = Gdn::session();

        // Define the primary key in this model's table.
        $this->defineSchema();

        // Add & apply any extra validation rules:
        $this->Validation->applyRule('Body', 'Required');
        $this->addInsertFields($FormPostValues);

        $this->EventArguments['FormPostValues'] = $FormPostValues;
        $this->fireEvent('BeforeSaveValidation');

        // Determine if spam check should be skipped.
        $SkipSpamCheck = (!empty($Options['NewConversation']));

        // Validate the form posted values
        $MessageID = false;
        if ($this->validate($FormPostValues) && !$this->checkForSpam('ConversationMessage', $SkipSpamCheck)) {
            $Fields = $this->Validation->schemaValidationFields(); // All fields on the form that relate to the schema
            touchValue('Format', $Fields, c('Garden.InputFormatter', 'Html'));

            $this->EventArguments['Fields'] = $Fields;
            $this->fireEvent('BeforeSave');

            $MessageID = $this->SQL->insert($this->Name, $Fields);
            $this->LastMessageID = $MessageID;
            $ConversationID = arrayValue('ConversationID', $Fields, 0);

            if (!$Conversation) {
                $Conversation = $this->SQL
                    ->getWhere('Conversation', array('ConversationID' => $ConversationID))
                    ->firstRow(DATASET_TYPE_ARRAY);
            }

            $Message = $this->getID($MessageID);
            $this->EventArguments['Conversation'] = $Conversation;
            $this->EventArguments['Message'] = $Message;
            $this->fireEvent('AfterSave');

            // Get the new message count for the conversation.
            $SQLR = $this->SQL
                ->select('MessageID', 'count', 'CountMessages')
                ->select('MessageID', 'max', 'LastMessageID')
                ->from('ConversationMessage')
                ->where('ConversationID', $ConversationID)
                ->get()->firstRow(DATASET_TYPE_ARRAY);
            if (sizeof($SQLR)) {
                list($CountMessages, $LastMessageID) = array_values($SQLR);
            } else {
                return;
            }

            // Update the conversation's DateUpdated field.
            $DateUpdated = Gdn_Format::toDateTime();

            $this->SQL
                ->update('Conversation c')
                ->set('CountMessages', $CountMessages)
                ->set('LastMessageID', $LastMessageID)
                ->set('UpdateUserID', Gdn::session()->UserID)
                ->set('DateUpdated', $DateUpdated)
                ->where('ConversationID', $ConversationID)
                ->put();

            // Update the last message of the users that were previously up-to-date on their read messages.
            $this->SQL
                ->update('UserConversation uc')
                ->set('uc.LastMessageID', $MessageID)
                ->set('uc.DateConversationUpdated', $DateUpdated)
                ->where('uc.ConversationID', $ConversationID)
                ->where('uc.Deleted', '0')
                ->where('uc.CountReadMessages', $CountMessages - 1)
                ->where('uc.UserID <>', $Session->UserID)
                ->put();

            // Update the date updated of the users that were not up-to-date.
            $this->SQL
                ->update('UserConversation uc')
                ->set('uc.DateConversationUpdated', $DateUpdated)
                ->where('uc.ConversationID', $ConversationID)
                ->where('uc.Deleted', '0')
                ->where('uc.CountReadMessages <>', $CountMessages - 1)
                ->where('uc.UserID <>', $Session->UserID)
                ->put();

            // Update the sending user.
            $this->SQL
                ->update('UserConversation uc')
                ->set('uc.CountReadMessages', $CountMessages)
                ->set('Deleted', 0)
                ->set('uc.DateConversationUpdated', $DateUpdated)
                ->where('ConversationID', $ConversationID)
                ->where('UserID', $Session->UserID)
                ->put();

            // Find users involved in this conversation
            $UserData = $this->SQL
                ->select('UserID')
                ->select('LastMessageID')
                ->select('Deleted')
                ->from('UserConversation')
                ->where('ConversationID', $ConversationID)
                ->get()->result(DATASET_TYPE_ARRAY);

            $UpdateCountUserIDs = array();
            $NotifyUserIDs = array();

            // Collapse for call to UpdateUserCache and ActivityModel.
            $InsertUserFound = false;
            foreach ($UserData as $UpdateUser) {
                $LastMessageID = val('LastMessageID', $UpdateUser);
                $UserID = val('UserID', $UpdateUser);
                $Deleted = val('Deleted', $UpdateUser);

                if ($UserID == val('InsertUserID', $Fields)) {
                    $InsertUserFound = true;
                    if ($Deleted) {
                        $this->SQL->put(
                            'UserConversation',
                            array('Deleted' => 0, 'DateConversationUpdated' => $DateUpdated),
                            array('ConversationID' => $ConversationID, 'UserID' => $UserID)
                        );
                    }
                }

                // Update unread for users that were up to date
                if ($LastMessageID == $MessageID) {
                    $UpdateCountUserIDs[] = $UserID;
                }

                // Send activities to users that have not deleted the conversation
                if (!$Deleted) {
                    $NotifyUserIDs[] = $UserID;
                }
            }

            if (!$InsertUserFound) {
                $UserConversation = array(
                    'UserID' => val('InsertUserID', $Fields),
                    'ConversationID' => $ConversationID,
                    'LastMessageID' => $LastMessageID,
                    'CountReadMessages' => $CountMessages,
                    'DateConversationUpdated' => $DateUpdated);
                $this->SQL->insert('UserConversation', $UserConversation);
            }

            if (sizeof($UpdateCountUserIDs)) {
                $ConversationModel = new ConversationModel();
                $ConversationModel->updateUserUnreadCount($UpdateCountUserIDs, true);
            }

            $this->fireEvent('AfterAdd');

            $activityModel = new ActivityModel();
            foreach ($NotifyUserIDs as $notifyUserID) {
                if ($Session->UserID == $notifyUserID) {
                    continue; // don't notify self.
                }
                // Notify the users of the new message.
                $activity = array(
                    'ActivityType' => 'ConversationMessage',
                    'ActivityUserID' => val('InsertUserID', $Fields),
                    'NotifyUserID' => $notifyUserID,
                    'HeadlineFormat' => t('HeadlineFormat.ConversationMessage', '{ActivityUserID,user} sent you a <a href="{Url,html}">message</a>'),
                    'RecordType' => 'Conversation',
                    'RecordID' => $ConversationID,
                    'Story' => val('Body', $Fields, ''),
                    'Format' => val('Format', $Fields, c('Garden.InputFormatter')),
                    'Route' => "/messages/{$ConversationID}#{$MessageID}",
                );

                if (c('Conversations.Subjects.Visible') && val('Subject', $Conversation, '')) {
                    $activity['HeadlineFormat'] = val('Subject', $Conversation, '');
                }
                $activityModel->queue($activity, 'ConversationMessage');
            }
            $activityModel->saveQueue();
        }
        return $MessageID;
    }

    /**
     * @param array $FormPostValues
     * @param bool $Insert
     * @return bool
     */
    public function validate($FormPostValues, $Insert = false) {
        $valid = parent::validate($FormPostValues, $Insert);

        if (!checkPermission('Garden.Moderation.Manage') && c('Conversations.MaxRecipients')) {
            $max = c('Conversations.MaxRecipients');
            if (isset($FormPostValues['RecipientUserID']) && count($FormPostValues['RecipientUserID']) > $max) {
                $this->Validation->addValidationResult(
                    'To',
                    plural($max, "You are limited to %s recipient.", "You are limited to %s recipients.")
                );
                $valid = false;
            }
        }
        return $valid;
    }
}
