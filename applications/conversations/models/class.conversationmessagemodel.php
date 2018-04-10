<?php
/**
 * Conversation message model.
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Conversations
 * @since 2.0
 */

/**
 * Manages messages in a conversation.
 */
class ConversationMessageModel extends ConversationsModel {
    /**
     * @var ConversationMessageModel The singleton instance of this class.
     */
    private static $instance;

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
     * {@inheritdoc}
     * @deprecated
     */
    public function get($orderFields = '', $orderDirection = 'asc', $limit = false, $pageNumber = false) {
        throw new \BadMethodCallException('ConversationMessageModel->get() is not supported.', 400);
    }

    /**
     * Get messages by conversation.
     *
     * Events: BeforeGet.
     *
     * @since 2.0.0
     * @access public
     *
     * @param int $conversationID Unique ID of conversation being viewed.
     * @param int $viewingUserID Unique ID of current user.
     * @param int $offset Number to skip.
     * @param int $limit Maximum to return.
     * @param array $wheres SQL conditions.
     * @return Gdn_DataSet SQL results.
     */
    public function getRecent($conversationID, $viewingUserID, $offset = '0', $limit = '', $wheres = '') {
        if ($limit == '') {
            $limit = Gdn::config('Conversations.Messages.PerPage', 50);
        }

        $offset = !is_numeric($offset) || $offset < 0 ? 0 : $offset;
        if (is_array($wheres)) {
            $this->SQL->where($wheres);
        }

        $this->fireEvent('BeforeGet');
        return $this->SQL
            ->select('cm.*')
            ->select('iu.Name', '', 'InsertName')
            ->select('iu.Email', '', 'InsertEmail')
            ->select('iu.Photo', '', 'InsertPhoto')
            ->from('ConversationMessage cm')
            ->join('Conversation c', 'cm.ConversationID = c.ConversationID')
            ->join('UserConversation uc', 'c.ConversationID = uc.ConversationID and uc.UserID = '.$viewingUserID, 'left')
            ->join('User iu', 'cm.InsertUserID = iu.UserID', 'left')
            ->beginWhereGroup()
            ->where('uc.DateCleared is null')
            ->orWhere('uc.DateCleared <', 'cm.DateInserted', true, false) // Make sure that cleared conversations do not show up unless they have new messages added.
            ->endWhereGroup()
            ->where('cm.ConversationID', $conversationID)
            ->orderBy('cm.DateInserted', 'asc')
            ->limit($limit, $offset)
            ->get();
    }

    /**
     * Get the data from the model based on its primary key.
     *
     * @param mixed $iD The value of the primary key in the database.
     * @param string $datasetType The format of the result dataset.
     * @param array $options Not used.
     * @return Gdn_DataSet
     */
    public function getID($iD, $datasetType = false, $options = []) {
        $result = $this->getWhere(["MessageID" => $iD])->firstRow($datasetType);
        return $result;
    }

    /**
     * Get only new messages from conversation.
     *
     * @since 2.0.0
     * @access public
     *
     * @param int $conversationID Unique ID of conversation being viewed.
     * @param int $lastMessageID Unique ID of last message to be viewed.
     * @return Gdn_DataSet SQL results.
     */
    public function getNew($conversationID, $lastMessageID) {
        $session = Gdn::session();
        $this->SQL->where('MessageID > ', $lastMessageID);
        return $this->getRecent($conversationID, $session->UserID);
    }

    /**
     * {@inheritdoc}
     * @deprecated
     */
    public function getCount($wheres = []) {
        deprecated('ConversationMessageModel->getCount()', 'ConversationMessageModel->getCountByConversation()');
        $args = func_get_args();
        return $this->getCountByConversation(
            val(0, $args, 0),
            val(1, $args, Gdn::session()->UserID),
            val(2, $args, '')
        );
    }

    /**
     * Get number of messages in a conversation.
     *
     * @since 2.0.0
     * @access public
     *
     * @param int $conversationID Unique ID of conversation being viewed.
     * @param int $viewingUserID Unique ID of current user.
     * @param array $wheres SQL conditions.
     * @return int Number of messages.
     */
    public function getCountByConversation($conversationID, $viewingUserID, $wheres = '') {
        if (is_array($wheres)) {
            $this->SQL->where($wheres);
        }

        $data = $this->SQL
            ->select('cm.MessageID', 'count', 'Count')
            ->from('ConversationMessage cm')
            ->join('Conversation c', 'cm.ConversationID = c.ConversationID')
            ->join('UserConversation uc', 'c.ConversationID = uc.ConversationID and uc.UserID = '.$viewingUserID)
            ->beginWhereGroup()
            ->where('uc.DateCleared is null')
            ->orWhere('uc.DateCleared >', 'c.DateUpdated', true, false) // Make sure that cleared conversations do not show up unless they have new messages added.
            ->endWhereGroup()
            ->groupBy('cm.ConversationID')
            ->where('cm.ConversationID', $conversationID)
            ->get();

        if ($data->numRows() > 0) {
            return $data->firstRow()->Count;
        }

        return 0;
    }

    /**
     * Get number of messages that meet criteria.
     *
     * @since 2.0.0
     * @access public
     *
     * @param array $wheres SQL conditions.
     * @return int Number of messages.
     */
    public function getCountWhere($wheres = '') {
        if (is_array($wheres)) {
            $this->SQL->where($wheres);
        }

        $data = $this->SQL
            ->select('MessageID', 'count', 'Count')
            ->from('ConversationMessage')
            ->get();

        if ($data->numRows() > 0) {
            return $data->firstRow()->Count;
        }

        return 0;
    }

    /**
     * Save message from form submission.
     *
     * @since 2.0.0
     * @access public
     *
     * @param array $formPostValues Values submitted via form.
     * @return int Unique ID of message created or updated.
     */
    public function save($formPostValues, $conversation = null, $options = []) {
        $session = Gdn::session();

        // Define the primary key in this model's table.
        $this->defineSchema();

        // Add & apply any extra validation rules:
        $this->Validation->applyRule('Body', 'Required');
        $this->addInsertFields($formPostValues);

        $this->EventArguments['FormPostValues'] = $formPostValues;
        $this->fireEvent('BeforeSaveValidation');

        $formIsValid = $this->validate($formPostValues);

        $checkFlood = true;
        // Determine if spam check should be skipped.
        if (!$session->User->Admin && !$session->checkPermission('Garden.Moderation.Manage')) {
            $checkFlood = empty($options['NewConversation']);
        }

        $floodCheckPassed = !$checkFlood;
        if ($formIsValid && $checkFlood) {
            $floodCheckPassed = !$this->checkUserSpamming(Gdn::session()->UserID, $this->floodGate);
        }

        // Validate the form posted values
        $messageID = false;
        if ($formIsValid && $floodCheckPassed) {
            $fields = $this->Validation->schemaValidationFields(); // All fields on the form that relate to the schema
            touchValue('Format', $fields, c('Garden.InputFormatter', 'Html'));

            $this->EventArguments['Fields'] = $fields;
            $this->fireEvent('BeforeSave');

            $messageID = $this->SQL->insert($this->Name, $fields);
            $this->LastMessageID = $messageID;
            $conversationID = val('ConversationID', $fields, 0);

            if (!$conversation) {
                $conversation = $this->SQL
                    ->getWhere('Conversation', ['ConversationID' => $conversationID])
                    ->firstRow(DATASET_TYPE_ARRAY);
            }

            $message = $this->getID($messageID);
            $this->EventArguments['Conversation'] = $conversation;
            $this->EventArguments['Message'] = $message;
            $this->fireEvent('AfterSave');

            // Get the new message count for the conversation.
            $result = $this->SQL
                ->select('MessageID', 'count', 'CountMessages')
                ->select('MessageID', 'max', 'LastMessageID')
                ->from('ConversationMessage')
                ->where('ConversationID', $conversationID)
                ->get()->firstRow(DATASET_TYPE_ARRAY);
            if (sizeof($result)) {
                list($countMessages, $lastMessageID) = array_values($result);
            } else {
                return;
            }

            // Update the conversation's DateUpdated field.
            $dateUpdated = Gdn_Format::toDateTime();

            $this->SQL
                ->update('Conversation c')
                ->set('CountMessages', $countMessages)
                ->set('LastMessageID', $lastMessageID)
                ->set('UpdateUserID', Gdn::session()->UserID)
                ->set('DateUpdated', $dateUpdated)
                ->where('ConversationID', $conversationID);
            if ($countMessages == 1) {
                $this->SQL->set('FirstMessageID', $lastMessageID);
            }
            $this->SQL->put();

            // Update the last message of the users that were previously up-to-date on their read messages.
            $this->SQL
                ->update('UserConversation uc')
                ->set('uc.LastMessageID', $messageID)
                ->set('uc.DateConversationUpdated', $dateUpdated)
                ->where('uc.ConversationID', $conversationID)
                ->where('uc.Deleted', '0')
                ->where('uc.CountReadMessages', $countMessages - 1)
                ->where('uc.UserID <>', $session->UserID)
                ->put();

            // Update the date updated of the users that were not up-to-date.
            $this->SQL
                ->update('UserConversation uc')
                ->set('uc.DateConversationUpdated', $dateUpdated)
                ->where('uc.ConversationID', $conversationID)
                ->where('uc.Deleted', '0')
                ->where('uc.CountReadMessages <>', $countMessages - 1)
                ->where('uc.UserID <>', $session->UserID)
                ->put();

            // Update the sending user.
            $this->SQL->update('UserConversation uc')
                ->set('uc.CountReadMessages', $countMessages);
            if ($countMessages == 1) {
                $this->SQL->set('uc.LastMessageID', $messageID);
            }
            $this->SQL->set('Deleted', 0)
                ->set('uc.DateConversationUpdated', $dateUpdated)
                ->where('ConversationID', $conversationID)
                ->where('UserID', $session->UserID)
                ->put();

            // Find users involved in this conversation
            $userData = $this->SQL
                ->select('UserID')
                ->select('LastMessageID')
                ->select('Deleted')
                ->from('UserConversation')
                ->where('ConversationID', $conversationID)
                ->get()->result(DATASET_TYPE_ARRAY);

            $updateCountUserIDs = [];
            $notifyUserIDs = [];

            // Collapse for call to UpdateUserCache and ActivityModel.
            $insertUserFound = false;
            foreach ($userData as $updateUser) {
                $lastMessageID = val('LastMessageID', $updateUser);
                $userID = val('UserID', $updateUser);
                $deleted = val('Deleted', $updateUser);

                if ($userID == val('InsertUserID', $fields)) {
                    $insertUserFound = true;
                    if ($deleted) {
                        $this->SQL->put(
                            'UserConversation',
                            ['Deleted' => 0, 'DateConversationUpdated' => $dateUpdated],
                            ['ConversationID' => $conversationID, 'UserID' => $userID]
                        );
                    }
                }

                // Update unread for users that were up to date
                if ($lastMessageID == $messageID) {
                    $updateCountUserIDs[] = $userID;
                }

                // Send activities to users that have not deleted the conversation
                if (!$deleted) {
                    $notifyUserIDs[] = $userID;
                }
            }

            if (!$insertUserFound) {
                $userConversation = [
                    'UserID' => val('InsertUserID', $fields),
                    'ConversationID' => $conversationID,
                    'LastMessageID' => $lastMessageID,
                    'CountReadMessages' => $countMessages,
                    'DateConversationUpdated' => $dateUpdated];
                $this->SQL->insert('UserConversation', $userConversation);
            }

            if (sizeof($updateCountUserIDs)) {
                $conversationModel = new ConversationModel();
                $conversationModel->updateUserUnreadCount($updateCountUserIDs, true);
            }

            $body = val('Body', $fields, '');
            $subject = val('Subject', $conversation, '');

            $this->EventArguments['Body'] = &$body;
            $this->EventArguments['Subject'] = &$subject;
            $this->fireEvent('AfterAdd');

            $this->notifyUsers($conversation, $message, $notifyUserIDs);
        }
        return $messageID;
    }

    /**
     * Return the singleton instance of this class.
     */
    public static function instance() {
        if (!isset(self::$instance)) {
            self::$instance = new ConversationMessageModel();
        }
        return self::$instance;
    }

    /**
     * @param array $formPostValues
     * @param bool $insert
     * @return bool
     */
    public function validate($formPostValues, $insert = false) {
        $valid = parent::validate($formPostValues, $insert);

        $maxRecipients = ConversationModel::getMaxRecipients();
        if ($maxRecipients) {
            if (isset($formPostValues['RecipientUserID']) && count($formPostValues['RecipientUserID']) > $maxRecipients) {
                $this->Validation->addValidationResult(
                    'To',
                    plural($maxRecipients, "You are limited to %s recipient.", "You are limited to %s recipients.")
                );
                $valid = false;
            }
        }
        return $valid;
    }
}
