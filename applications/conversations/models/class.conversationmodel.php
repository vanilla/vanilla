<?php
/**
 * Conversation model.
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Conversations
 * @since 2.0
 */

/**
 * Manages conversation data.
 */
class ConversationModel extends ConversationsModel {

    /**
     * Class constructor. Defines the related database table name.
     *
     * @since 2.0.0
     * @access public
     */
    public function __construct() {
        parent::__construct('Conversation');
    }

    /**
     * Build generic part of conversation query.
     *
     * @since 2.0.0
     * @access public
     *
     * @param int $viewingUserID Unique ID of current user.
     */
    public function conversationQuery($viewingUserID, $join = '') {
        $this->SQL
            ->select('c.*')
            ->select('lm.InsertUserID', '', 'LastMessageUserID')
            ->select('lm.DateInserted', '', 'DateLastMessage')
            ->select('lm.Body', '', 'LastMessage')
            ->select('lm.Format')
            ->select('lmu.Name', '', 'LastMessageName')
            ->select('lmu.Photo', '', 'LastMessagePhoto')
            ->from('Conversation c');


        if ($viewingUserID) {
            $this->SQL
                ->select('c.CountMessages - uc.CountReadMessages', '', 'CountNewMessages')
                ->select('uc.LastMessageID, uc.CountReadMessages, uc.DateLastViewed, uc.Bookmarked')
                ->join('UserConversation uc', "c.ConversationID = uc.ConversationID and uc.UserID = $viewingUserID")
                ->join('ConversationMessage lm', 'uc.LastMessageID = lm.MessageID')
                ->join('User lmu', 'lm.InsertUserID = lmu.UserID')
                ->where('uc.Deleted', 0);
        } else {
            $this->SQL
                ->select('0', '', 'CountNewMessages')
                ->select('c.CountMessages', '', 'CountReadMessages')
                ->select('lm.DateInserted', '', 'DateLastViewed')
                ->select('0', '', 'Bookmarked')
                ->join('ConversationMessage lm', 'c.LastMessageID = lm.MessageID')
                ->join('User lmu', 'lm.InsertUserID = lmu.UserID');
        }
    }

    public function counts($column, $from = false, $to = false, $max = false) {
        $result = ['Complete' => true];
        switch ($column) {
            case 'CountMessages':
                $this->Database->query(DBAModel::getCountSQL('count', 'Conversation', 'ConversationMessage', $column, 'MessageID'));
                break;
            case 'CountParticipants':
                $this->SQL->update('Conversation c')
                    ->set('c.CountParticipants', '(select count(uc.ConversationID) from GDN_UserConversation uc where uc.ConversationID = c.ConversationID and uc.Deleted = 0)', false, false)
                    ->put();
                break;
            case 'FirstMessageID':
                $this->Database->query(DBAModel::getCountSQL('min', 'Conversation', 'ConversationMessage', $column, 'MessageID'));
                break;
            case 'LastMessageID':
                $this->Database->query(DBAModel::getCountSQL('max', 'Conversation', 'ConversationMessage', $column, 'MessageID'));
                break;
            case 'DateUpdated':
                $this->Database->query(DBAModel::getCountSQL('max', 'Conversation', 'ConversationMessage', $column, 'DateInserted'));
                break;
            case 'UpdateUserID':
                $this->SQL
                    ->update('Conversation c')
                    ->join('ConversationMessage m', 'c.LastMessageID = m.MessageID')
                    ->set('c.UpdateUserID', 'm.InsertUserID', false, false)
                    ->put();
                break;
            default:
                throw new Gdn_UserException("Unknown column $column");
        }
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function get($orderFields = '', $orderDirection = '', $limit = false, $pageNumber = false) {
        if (is_numeric($orderFields) || is_numeric($orderDirection)) {
            deprecated('ConversationModel->get()', 'ConversationModel->getInbox()');
            return $this->getInbox($orderFields, $limit, $orderDirection);
        }
    }


    /**
     * Get list of conversations.
     *
     * Events: BeforeGet.
     *
     * @param int $viewingUserID Unique ID of current user.
     * @param int|string $limit Maximum to return.
     * @param int|string $offset Number to skip.
     * @return Gdn_DataSet SQL results.
     */
    public function getInbox($viewingUserID, $limit = '', $offset = '0') {
        if ($limit == '') {
            $limit = Gdn::config('Conversations.Conversations.PerPage', 30);
        }

        $offset = !is_numeric($offset) || $offset < 0 ? 0 : $offset;

        // Grab the base list of conversations.
        $data = $this->SQL
            ->select('c.*')
            ->select('uc.CountReadMessages')
            ->select('uc.LastMessageID', '', 'UserLastMessageID')
            ->from('UserConversation uc')
            ->join('Conversation c', 'uc.ConversationID = c.ConversationID')
            ->where('uc.UserID', $viewingUserID)
            ->where('uc.Deleted', 0)
            ->orderBy('c.DateUpdated', 'desc')
            ->limit($limit, $offset)
            ->get()->resultArray();

        $this->joinLastMessages($data);
        return $data;
    }

    /**
     * Get a list of conversations for a user's inbox. This is an optimized version of ConversationModel::get().
     *
     * @param int $userID The user looking at the conversations.
     * @param int $offset Number to skip.
     * @param int $limit Maximum to return.
     * @return Gdn_DataSet
     */
    public function get2($userID, $offset = 0, $limit = 0) {
        if ($limit <= 0) {
            $limit = c('Conversations.Conversations.PerPage', 30);
        }

        // The self join is intentional in order to force the query to us an index-scan instead of a table-scan.
        $data = $this->SQL
            ->select('c.*')
            ->select('uc2.DateLastViewed')
            ->select('uc2.CountReadMessages')
            ->select('uc2.LastMessageID', '', 'UserLastMessageID')
            ->from('UserConversation uc')
            ->join('UserConversation uc2', 'uc.ConversationID = uc2.ConversationID and uc.UserID = uc2.UserID')
            ->join('Conversation c', 'c.ConversationID = uc2.ConversationID')
            ->where('uc.UserID', $userID)
            ->where('uc.Deleted', 0)
            ->orderBy('uc.DateConversationUpdated', 'desc')
            ->limit($limit, $offset)
            ->get();

        $data->datasetType(DATASET_TYPE_ARRAY);
        $result =& $data->result();

        // Add some calculated fields.
        foreach ($result as &$row) {
            if ($row['UserLastMessageID']) {
                $row['LastMessageID'] = $row['UserLastMessageID'];
            }
            $row['CountNewMessages'] = $row['CountMessages'] - $row['CountReadMessages'];
            unset($row['UserLastMessageID']);
        }

        // Join the participants.
        $this->joinParticipants($result);

        // Join in the last message.
        Gdn_DataSet::join(
            $result,
            [
                'table' => 'ConversationMessage',
                'prefix' => 'Last',
                'parent' => 'LastMessageID',
                'child' => 'MessageID',
                'InsertUserID', 'DateInserted', 'Body', 'Format']
        );

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function getCount($wheres = []) {
        if (is_numeric($wheres)) {
            deprecated('ConversationModel->getCount(int, array)', 'ConversationModel->getCountInbox()');
            $args = func_get_args();
            return $this->getCountInbox($wheres, val(1, $args));
        }
        return parent::getCount();
    }

    /**
     * Get number of conversations involving current user.
     *
     * @param int $viewingUserID Unique ID of current user.
     * @param array $wheres SQL conditions.
     * @return int Number of messages.
     */
    public function getCountInbox($viewingUserID, $wheres = '') {
        if (is_array($wheres)) {
            $this->SQL->where($wheres);
        }

        return $this->SQL
            ->select('uc.UserID', 'count', 'Count')
            ->from('UserConversation uc')
            ->where('uc.UserID', $viewingUserID)
            ->get()
            ->value('Count', 0);
    }

    /**
     * Get number of conversations that meet criteria.
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
            ->select('ConversationID', 'count', 'Count')
            ->from('Conversation')
            ->get();

        if ($data->numRows() > 0) {
            return $data->firstRow()->Count;
        }

        return 0;
    }

    /**
     * Get meta data of a single conversation.
     *
     * @param int $conversationID Unique ID of conversation.
     * @param string $datasetType The format of the resulting conversation.
     * @param array $options Options to modify the get. Currently supports `viewingUserID`.
     * @return array|stdClass|false Returns a conversation or false on failure.
     */
    public function getID($conversationID, $datasetType = false, $options = []) {
        if (is_numeric($datasetType)) {
            deprecated('ConversationModel->getID(int, int)', 'ConversationModel->getID(int, string, array)');
            $viewingUserID = $datasetType;
            $datasetType = false;
        } else {
            $viewingUserID = val('viewingUserID', $options);
        }
        $datasetType = $datasetType ?: DATASET_TYPE_OBJECT;

        // Get the conversation.
        $conversation = $this->getWhere(['ConversationID' => $conversationID])->firstRow(DATASET_TYPE_ARRAY);

        if ($conversation) {
            if ($viewingUserID) {
                $data = $this->SQL->getWhere(
                    'UserConversation',
                    ['ConversationID' => $conversationID, 'UserID' => $viewingUserID]
                )->firstRow(DATASET_TYPE_ARRAY);

                // Convert the array.
                $userConversation = arrayTranslate($data, ['LastMessageID', 'CountReadMessages', 'DateLastViewed', 'Bookmarked']);
                $userConversation['CountNewMessages'] = $conversation['CountMessages'] - $data['CountReadMessages'];
                if ($userConversation['LastMessageID'] === null) {
                    unset($userConversation['LastMessageID']);
                }
            } else {
                $userConversation = ['CountNewMessages' => 0, 'CountReadMessages' => $conversation['CountMessages'], 'DateLastViewed' => $conversation['DateUpdated']];
            }
            $conversation = array_merge($conversation, $userConversation);

            if ($datasetType === DATASET_TYPE_OBJECT) {
                $conversation = (object)$conversation;
            }
        }

        return $conversation;
    }

    /**
     * Get how many recipients current user can send a message to.
     *
     * @return int|bool A maximum number of recipients or FALSE for unlimited.
     */
    public static function getMaxRecipients() {
        // Moderators can add as many as they want.
        if (Gdn::session()->checkRankedPermission('Garden.Moderation.Manage')) {
            return false;
        }

        // Start conservative.
        $maxRecipients = c('Conversations.MaxRecipients', 5);

        // Verified users are more trusted.
        if (val('Verified', Gdn::session()->User)) {
            $verifiedMax = c('Conversations.MaxRecipientsVerified', 50);
            // Only allow raising the limit for verified users.
            $maxRecipients = ($verifiedMax > $maxRecipients) ? $verifiedMax : $maxRecipients;
        }

        return $maxRecipients;
    }

    /**
     * Get all users involved in conversation.
     *
     * @since 2.0.0
     * @access public
     *
     * @param int $conversationID Unique ID of conversation.
     * @param int $limit The number of recipients to grab.
     * @return Gdn_DataSet SQL results.
     */
    public function getRecipients($conversationID, $limit = 1000) {
        $data = $this->SQL
            ->select('uc.*')
            ->from('UserConversation uc')
            ->where('uc.ConversationID', $conversationID)
            ->limit($limit)
            ->get();

        $options = ['Join'  => ['Name', 'Photo']];
        if (Gdn::session()->checkPermission(['Garden.PersonalInfo.View', 'Garden.Users.Edit'], false)) {
            $options['Join'][] = 'Email';
        }

        Gdn::userModel()->joinUsers($data->result(), ['UserID'], $options);
        return $data;
    }

    /**
     *
     *
     * @param array $data
     * @param int $max
     */
    public function joinParticipants(&$data, $max = 5) {
        // Loop through the data and find the conversations with >= $Max participants.
        $ids = [];
        foreach ($data as $row) {
            if ($row['CountParticipants'] <= $max) {
                $ids[] = $row['ConversationID'];
            }
        }

        $users = $this->SQL
            ->select('*')
            ->from('UserConversation uc')
            ->whereIn('uc.ConversationID', $ids)
            ->get()->resultArray();

        Gdn::userModel()->joinUsers($users, ['UserID']);

        $users = Gdn_DataSet::index($users, ['ConversationID'], ['Unique' => false]);

        foreach ($data as &$row) {
            $conversationID = $row['ConversationID'];
            if (isset($users[$conversationID])) {
                $row['Participants'] = $users[$conversationID];
            } else {
                $row['Participants'] = [];
            }
        }
    }

    /**
     * Figure out whether or not a user is in a conversation.
     * @param int $conversationID
     * @param int $userID
     * @return bool
     */
    public function inConversation($conversationID, $userID) {
        $row = $this->SQL
            ->getWhere('UserConversation', ['ConversationID' => $conversationID, 'UserID' => $userID])
            ->firstRow(DATASET_TYPE_ARRAY);
        if (!$row) {
            return false;
        }
        return empty($row['Deleted']);
    }

    public function joinLastMessages(&$data) {
        // Grab all of the last message IDs.
        $iDs = [];
        foreach ($data as &$row) {
            $row['CountNewMessages'] = $row['CountMessages'] - $row['CountReadMessages'];
            if ($row['UserLastMessageID']) {
                $row['LastMessageID'] = $row['UserLastMessageID'];
            }
            $iDs[] = $row['LastMessageID'];
        }

        $messages = $this->SQL->whereIn('MessageID', $iDs)->get('ConversationMessage')->resultArray();
        $messages = Gdn_DataSet::index($messages, ['MessageID']);

        foreach ($data as &$row) {
            $iD = $row['LastMessageID'];
            if (isset($messages[$iD])) {
                $m = $messages[$iD];
                $row['LastUserID'] = $m['InsertUserID'];
                $row['DateLastMessage'] = $m['DateInserted'];
                $row['LastMessage'] = $m['Body'];
                $row['Format'] = $m['Format'];

            } else {
                $row['LastMessageUserID'] = $row['InsertUserID'];
                $row['DateLastMessage'] = $row['DateInserted'];
                $row['LastMessage'] = null;
                $row['Format'] = null;
            }
        }

        Gdn::userModel()->joinUsers($data, ['LastUserID']);
    }


    /**
     * Gets a nice title to represent the participants in a conversation.
     *
     * @param array|object $conversation The conversation to get the participants for.
     * @param bool $html Whether or not to return HTML.
     * @param int $max The maximum number of participants to show in the list.
     * @return string Returns a title for the conversation.
     */
    public static function participantTitle($conversation, $html = true, $max = 3) {
        $participants = val('Participants', $conversation);
        $total = (int)val('CountParticipants', $conversation);
        $myID = Gdn::session()->UserID;
        $foundMe = false;

        // Try getting people that haven't left the conversation and aren't you.
        $users = [];
        $i = 0;
        foreach ($participants as $row) {
            if (val('UserID', $row) == $myID) {
                $foundMe = true;
                continue;
            }
            if (val('Deleted', $row)) {
                continue;
            }
            if ($html) {
                $users[] = userAnchor($row);
            } else {
                $users[] = val('Name', $row);
            }

            $i++;
            if ($i > $max || ($total > $max && $i === $max)) {
                break;
            }
        }

        $count = count($users);

        if ($count === 0) {
            if ($foundMe) {
                $result = t('Just you');
            } elseif ($total)
                $result = plural($total, '%s person', '%s people');
            else {
                $result = t('Nobody');
            }
        } else {
            $px = implode(', ', $users);

            if ($count + 1 === $total && $foundMe) {
                $result = $px;
            } elseif ($total - $count === 1) {
                $result = sprintf(t('%s and 1 other'), $px);
            } elseif ($total > $count) {
                $result = sprintf(t('%s and %s others'), $px, $total - $count);
            } else {
                $result = $px;
            }
        }

        return $result;
    }

    /**
     * Save conversation from form submission.
     *
     * @param array $formPostValues Values submitted via form.
     * @param array $settings
     *   - ConversationOnly If set, no message will be created.
     * @return int Unique ID of conversation created or updated.
     */
    public function save($formPostValues, $settings = []) {
        $deprecated = $settings instanceof ConversationMessageModel;
        $createMessage =  $deprecated || empty($settings['ConversationOnly']);

        if ($createMessage) {
            if ($deprecated) {
                deprecated('ConversationModel->save(array, ConversationMessageModel)');
                $messageModel = $settings;
            } else {
                $messageModel = ConversationMessageModel::instance();
            }
            $messageModel->defineSchema();
        }

        // Define the primary key in this model's table.
        $this->defineSchema();

        $this->EventArguments['FormPostValues'] = $formPostValues;
        $this->fireEvent('BeforeSaveValidation');

        if (!val('RecipientUserID', $formPostValues) && isset($formPostValues['To'])) {
            $to = explode(',', $formPostValues['To']);
            $to = array_map('trim', $to);

            $recipientUserIDs = $this->SQL
                ->select('UserID')
                ->from('User')
                ->whereIn('Name', $to)
                ->get()->resultArray();
            $recipientUserIDs = array_column($recipientUserIDs, 'UserID');
            $formPostValues['RecipientUserID'] = $recipientUserIDs;
        }

        if (c('Garden.ForceInputFormatter')) {
            $formPostValues['Format'] = c('Garden.InputFormatter');
        }

        if ($createMessage) {
            // Add & apply any extra validation rules:
            $this->Validation->applyRule('Body', 'Required');
            $messageModel->Validation->applyRule('Body', 'Required');
        }

        // Make sure that there is at least one recipient
        $this->Validation->addRule('OneOrMoreArrayItemRequired', 'function:ValidateOneOrMoreArrayItemRequired');
        $this->Validation->applyRule('RecipientUserID', 'OneOrMoreArrayItemRequired');

        // Add insert/update fields
        $this->addInsertFields($formPostValues);
        $this->addUpdateFields($formPostValues);

        $conversationValid = $this->validate($formPostValues);

        if ($conversationValid && $createMessage) {
            $isValidMessageModel = $messageModel->validate($formPostValues);
        } else {
            $isValidMessageModel = $conversationValid;
        }

        // Validate the form posted values
        $conversationID = false;
        if ($conversationValid && $isValidMessageModel
            && !$this->checkUserSpamming(Gdn::session()->UserID, $this->floodGate)) {

            $fields = $this->Validation->validationFields(); // All fields on the form that relate to the schema

            // Define the recipients, and make sure that the sender is in the list
            $recipientUserIDs = val('RecipientUserID', $fields, 0);

            if (!in_array($formPostValues['InsertUserID'], $recipientUserIDs)) {
                $recipientUserIDs[] = $formPostValues['InsertUserID'];
            }

            // Also make sure there are no duplicates in the recipient list
            $recipientUserIDs = array_unique($recipientUserIDs);
            sort($recipientUserIDs);
            $fields = $this->Validation->schemaValidationFields(); // All fields on the form that relate to the schema
            $conversationID = $this->SQL->insert($this->Name, $fields);
            $formPostValues['ConversationID'] = $conversationID;

            if ($createMessage) {
                // Notify the message model that it's being called as a direct result
                // of a new conversation being created. As of now, this is being used
                // so that spam checks between new conversations and conversation
                // messages each have a separate counter. Without this, a new
                // conversation will cause itself AND the message model spam counter
                // to increment by 1.
                $messageID = $messageModel->save($formPostValues, null, [
                    'NewConversation' => true
                ]);

                $this->SQL->update('Conversation')
                    ->set('FirstMessageID', $messageID)
                    ->where('ConversationID', $conversationID)
                    ->put();
            }

            // Now that the conversation (and potentially the message) have been inserted, insert all of the recipients
            foreach ($recipientUserIDs as $userID) {
                $countReadMessages = $userID == $formPostValues['InsertUserID'] ? 1 : 0;

                $recipientData = [
                    'UserID' => $userID,
                    'ConversationID' => $conversationID,
                    'DateConversationUpdated' => $formPostValues['DateUpdated']
                ];

                if ($createMessage) {
                    $recipientData['LastMessageID'] = $messageID;
                    $recipientData['CountReadMessages'] = $countReadMessages;
                }

                $this->SQL->options('Ignore', true)->insert('UserConversation', $recipientData);
            }

            if ($createMessage) {
                // And update the CountUnreadConversations count on each user related to the discussion.
                $this->updateUserUnreadCount(array_diff($recipientUserIDs, [$formPostValues['InsertUserID']]));
            }

            $this->updateParticipantCount($conversationID);

            $body = val('Body', $formPostValues, '');
            $subject = val('Subject', $fields, '');


            $this->EventArguments['Recipients'] = $recipientUserIDs;
            $conversation = $this->getID($conversationID);
            $this->EventArguments['Conversation'] = $conversation;
            $this->EventArguments['Subject'] = &$subject;
            if ($createMessage) {
                $message = $messageModel->getID($messageID, DATASET_TYPE_ARRAY);
                $this->EventArguments['Message'] = $message;
                $this->EventArguments['Body'] = &$body;
            }
            $this->fireEvent('AfterAdd');

            $conversation = (array)$conversation;

            // Add notifications
            if ($createMessage) {

                $unreadData = $this->SQL
                    ->select('uc.UserID')
                    ->from('UserConversation uc')
                    ->where('uc.ConversationID', $conversation['ConversationID'])// hopefully coax this index.
                    ->where('uc.UserID <>', $conversation['InsertUserID'])
                    ->get()
                    ->result(DATASET_TYPE_ARRAY);

                $notifyUserIDs = array_column($unreadData, 'UserID');

                $this->notifyUsers($conversation, $message, $notifyUserIDs);
            }

        } else if ($createMessage) {
            // Make sure that all of the validation results from both validations are present for view by the form
            foreach ($messageModel->validationResults() as $fieldName => $results) {
                foreach ($results as $result) {
                    $this->Validation->addValidationResult($fieldName, $result);
                }
            }
        }

        return $conversationID;
    }

    /**
     * Clear a conversation for a specific user id.
     *
     * @since 2.0.0
     * @access public
     *
     * @param int $conversationID Unique ID of conversation effected.
     * @param int $clearingUserID Unique ID of current user.
     */
    public function clear($conversationID, $clearingUserID) {
        $this->SQL->update('UserConversation')
            ->set('Deleted', 1)
            ->set('DateLastViewed', Gdn_Format::toDateTime())
            ->where('UserID', $clearingUserID)
            ->where('ConversationID', $conversationID)
            ->put();

        $this->countUnread($clearingUserID);
        $this->updateParticipantCount($conversationID);
    }

    /**
     * Count unread messages.
     *
     * @param int $userID Unique ID for user being queried.
     * @param bool $save Whether to update user record.
     * @return int
     */
    public function countUnread($userID, $save = true) {
        // Also update the unread conversation count for this user
        $countUnread = $this->SQL
            ->select('c.ConversationID', 'count', 'CountUnread')
            ->from('UserConversation uc')
            ->join('Conversation c', 'c.ConversationID = uc.ConversationID and uc.CountReadMessages < c.CountMessages')
            ->where('uc.UserID', $userID)
            ->where('uc.Deleted', 0)
            ->get()->value('CountUnread', 0);

        if ($save) {
            Gdn::userModel()->setField($userID, 'CountUnreadConversations', $countUnread);
        }

        return $countUnread;
    }

    /**
     * Update a conversation as read for a specific user id.
     *
     * @since 2.0.0
     * @access public
     *
     * @param int $conversationID Unique ID of conversation effected.
     * @param int $readingUserID Unique ID of current user.
     */
    public function markRead($conversationID, $readingUserID) {
        // Update the the read conversation count for the user.
        $this->SQL->update('UserConversation uc')
            ->join('Conversation c', 'c.ConversationID = uc.ConversationID')
            ->set('uc.CountReadMessages', 'c.CountMessages', false)
            ->set('uc.DateLastViewed', Gdn_Format::toDateTime())
            ->set('uc.LastMessageID', 'c.LastMessageID', false)
            ->where('c.ConversationID', $conversationID)
            ->where('uc.ConversationID', $conversationID)
            ->where('uc.UserID', $readingUserID)
            ->put();

        // Also update the unread conversation count for this user
        $countUnread = $this->countUnread($readingUserID);

        // Also write through to the current session user.
        if ($readingUserID > 0 && $readingUserID == Gdn::session()->UserID) {
            Gdn::session()->User->CountUnreadConversations = $countUnread;
        }
    }

    /**
     * Bookmark (or unbookmark) a conversation for a specific user id.
     *
     * @since 2.0.0
     * @access public
     *
     * @param int $conversationID Unique ID of conversation effected.
     * @param int $userID Unique ID of current user.
     * @return bool Whether it is currently bookmarked.
     */
    public function bookmark($conversationID, $userID) {
        $bookmark = false;
        $discussion = $this->getID($conversationID, $userID);
        if (is_object($discussion)) {
            $bookmark = $discussion->Bookmark == '0' ? '1' : '0';
            $this->SQL->update('UserConversation')
                ->set('Bookmark', $bookmark)
                ->where('ConversationID', $conversationID)
                ->where('UserID', $userID)
                ->put();
            $bookmark == '1' ? true : false;
        }
        return $bookmark;
    }

    /**
     * Add another user to the conversation.
     *
     * @since 2.0.0
     * @access public
     *
     * @param int $conversationID Unique ID of conversation effected.
     * @param int|array $userIDs Unique ID of the user(s).
     * @return True if the operation was a success, false if the maximum number of recipients was busted.
     *
     */
    public function addUserToConversation($conversationID, $userIDs) {
        if (!is_array($userIDs)) {
            $userIDs = [$userIDs];
        }

        // First define the current users in the conversation
        $oldContributorData = $this->getRecipients($conversationID);
        $maxRecipients = self::getMaxRecipients();
        if ($maxRecipients && (count($oldContributorData) + count($userIDs) > $maxRecipients + 1)) {
            return false;
        }

        $oldContributorData = Gdn_DataSet::index($oldContributorData, 'UserID');
        $addedUserIDs = [];

        // Get some information about this conversation
        $conversationData = $this->SQL
            ->select('LastMessageID')
            ->select('DateUpdated')
            ->select('CountMessages')
            ->from('Conversation')
            ->where('ConversationID', $conversationID)
            ->get()
            ->firstRow(DATASET_TYPE_ARRAY);

        $this->EventArguments['ConversationID'] = $conversationID;
        $this->EventArguments['UserIDs'] = &$userIDs;
        $this->EventArguments['OldContributorData'] = $oldContributorData;
        $this->fireEvent('beforeAddUser');

        // Add the user(s) if they are not already in the conversation
        foreach ($userIDs as $newUserID) {
            if (!array_key_exists($newUserID, $oldContributorData)) {
                $addedUserIDs[] = $newUserID;
                $this->SQL->insert('UserConversation', [
                    'UserID' => $newUserID,
                    'ConversationID' => $conversationID,
                    'LastMessageID' => $conversationData['LastMessageID'],
                    'CountReadMessages' => 0,
                    'DateConversationUpdated' => $conversationData['DateUpdated']
                ]);
            } elseif ($oldContributorData[$newUserID]->Deleted) {
                $addedUserIDs[] = $newUserID;

                $this->SQL->put(
                    'UserConversation',
                    ['Deleted' => 0],
                    ['ConversationID' => $conversationID, 'UserID' => $newUserID]
                );
            }
        }
        if (count($addedUserIDs) > 0) {
            $activityModel = new ActivityModel();
            foreach ($addedUserIDs as $addedUserID) {
                $activityModel->queue(
                    [
                    'ActivityType' => 'AddedToConversation',
                    'NotifyUserID' => $addedUserID,
                    'HeadlineFormat' => t('You were added to a conversation.', '{ActivityUserID,User} added you to a <a href="{Url,htmlencode}">conversation</a>.'),
                    'Route' => '/messages/'.$conversationID
                    ],
                    'ConversationMessage'
                );
            }
            $activityModel->saveQueue();

            if ($conversationData['CountMessages'] != 0) {
                $this->updateUserUnreadCount($addedUserIDs);
            }
            $this->updateParticipantCount($conversationID);
        }

        return true;
    }

    /**
     * Are we allowed to add more recipients?
     *
     * If we pass $countRecipients then $conversationID isn't needed (set to zero).
     *
     * @param int $conversationID Unique ID of the conversation.
     * @param int $countRecipients Optionally skip needing to query the count by passing it.
     * @return bool Whether user may add more recipients to conversation.
     */
    public function addUserAllowed($conversationID = 0, $countRecipients = 0) {
        // Determine whether recipients can be added
        $canAddRecipients = true;
        $maxRecipients = self::getMaxRecipients();

        // Avoid a query if we already know we can add. MaxRecipients being unset means unlimited.
        if ($maxRecipients) {
            if (!$countRecipients) {
                // Count current recipients
                $conversationModel = new ConversationModel();
                $countRecipients = $conversationModel->getRecipients($conversationID);
            }

            // Add 1 because sender counts as a recipient.
            $canAddRecipients = count($countRecipients) < ($maxRecipients + 1);
        }

        return $canAddRecipients;
    }

    /**
     * Update the count of participants.
     *
     * @param int $conversationID
     */
    public function updateParticipantCount($conversationID) {
        if (!$conversationID) {
            return;
        }

        $count = $this->SQL
            ->select('uc.UserID', 'count', 'CountParticipants')
            ->from('UserConversation uc')
            ->where('uc.ConversationID', $conversationID)
            ->where('uc.Deleted', 0)
            ->get()->value('CountParticipants', 0);

        $this->setField($conversationID, 'CountParticipants', $count);
    }

    /**
     * Update users' unread conversation counter.
     *
     * @param array $userIDs Array of ints.
     * @param bool $skipSelf Whether to omit current user.
     */
    public function updateUserUnreadCount($userIDs, $skipSelf = false) {

        // Get the current user out of this array
        if ($skipSelf) {
            $userIDs = array_diff($userIDs, [Gdn::session()->UserID]);
        }

        // Update the CountUnreadConversations count on each user related to the discussion.
        $this->SQL
            ->update('User')
            ->set('CountUnreadConversations', 'coalesce(CountUnreadConversations, 0) + 1', false)
            ->whereIn('UserID', $userIDs)
            ->put();

        // Query it back since it was an expression
        $userData = $this->SQL
            ->select('UserID')
            ->select('CountUnreadConversations')
            ->from('User')
            ->whereIn('UserID', $userIDs)
            ->get()->result(DATASET_TYPE_ARRAY);

        // Update the user caches
        foreach ($userData as $updateUser) {
            $updateUserID = val('UserID', $updateUser);
            $countUnreadConversations = val('CountUnreadConversations', $updateUser);
            $countUnreadConversations = (is_numeric($countUnreadConversations)) ? $countUnreadConversations : 1;
            Gdn::userModel()->updateUserCache($updateUserID, 'CountUnreadConversations', $countUnreadConversations);
        }
    }
}
