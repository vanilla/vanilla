<?php
/**
 * Conversation model.
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
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
     * @param int $ViewingUserID Unique ID of current user.
     */
    public function conversationQuery($ViewingUserID, $Join = '') {
        $this->SQL
            ->select('c.*')
            ->select('lm.InsertUserID', '', 'LastMessageUserID')
            ->select('lm.DateInserted', '', 'DateLastMessage')
            ->select('lm.Body', '', 'LastMessage')
            ->select('lm.Format')
            ->select('lmu.Name', '', 'LastMessageName')
            ->select('lmu.Photo', '', 'LastMessagePhoto')
            ->from('Conversation c');


        if ($ViewingUserID) {
            $this->SQL
                ->select('c.CountMessages - uc.CountReadMessages', '', 'CountNewMessages')
                ->select('uc.LastMessageID, uc.CountReadMessages, uc.DateLastViewed, uc.Bookmarked')
                ->join('UserConversation uc', "c.ConversationID = uc.ConversationID and uc.UserID = $ViewingUserID")
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

    public function counts($Column, $From = false, $To = false, $Max = false) {
        $Result = array('Complete' => true);
        switch ($Column) {
            case 'CountMessages':
                $this->Database->query(DBAModel::getCountSQL('count', 'Conversation', 'ConversationMessage', $Column, 'MessageID'));
                break;
            case 'CountParticipants':
                $this->SQL->update('Conversation c')
                    ->set('c.CountParticipants', '(select count(uc.ConversationID) from GDN_UserConversation uc where uc.ConversationID = c.ConversationID and uc.Deleted = 0)', false, false)
                    ->put();
                break;
            case 'FirstMessageID':
                $this->Database->query(DBAModel::getCountSQL('min', 'Conversation', 'ConversationMessage', $Column, 'MessageID'));
                break;
            case 'LastMessageID':
                $this->Database->query(DBAModel::getCountSQL('max', 'Conversation', 'ConversationMessage', $Column, 'MessageID'));
                break;
            case 'DateUpdated':
                $this->Database->query(DBAModel::getCountSQL('max', 'Conversation', 'ConversationMessage', $Column, 'DateInserted'));
                break;
            case 'UpdateUserID':
                $this->SQL
                    ->update('Conversation c')
                    ->join('ConversationMessage m', 'c.LastMessageID = m.MessageID')
                    ->set('c.UpdateUserID', 'm.InsertUserID', false, false)
                    ->put();
                break;
            default:
                throw new Gdn_UserException("Unknown column $Column");
        }
        return $Result;
    }

    /**
     * Get list of conversations.
     *
     * Events: BeforeGet.
     *
     * @since 2.0.0
     * @access public
     *
     * @param int $ViewingUserID Unique ID of current user.
     * @param int $Offset Number to skip.
     * @param int $Limit Maximum to return.
     * @return Gdn_DataSet SQL results.
     */
    public function get($ViewingUserID, $Offset = '0', $Limit = '') {
        if ($Limit == '') {
            $Limit = Gdn::config('Conversations.Conversations.PerPage', 30);
        }

        $Offset = !is_numeric($Offset) || $Offset < 0 ? 0 : $Offset;

        // Grab the base list of conversations.
        $Data = $this->SQL
            ->select('c.*')
            ->select('uc.CountReadMessages')
            ->select('uc.LastMessageID', '', 'UserLastMessageID')
            ->from('UserConversation uc')
            ->join('Conversation c', 'uc.ConversationID = c.ConversationID')
            ->where('uc.UserID', $ViewingUserID)
            ->where('uc.Deleted', 0)
            ->orderBy('c.DateUpdated', 'desc')
            ->limit($Limit, $Offset)
            ->get()->resultArray();

        $this->joinLastMessages($Data);
        return $Data;
    }

    /**
     * Get a list of conversaitons for a user's inbox. This is an optimized version of ConversationModel::get().
     *
     * @param int $UserID
     * @param int $Offset Number to skip.
     * @param int $Limit Maximum to return.
     */
    public function get2($UserID, $Offset = 0, $Limit = false) {
        if (!$Limit) {
            $Limit = c('Conversations.Conversations.PerPage', 30);
        }

        // The self join is intentional in order to force the query to us an index-scan instead of a table-scan.
        $Data = $this->SQL
            ->select('c.*')
            ->select('uc2.DateLastViewed')
            ->select('uc2.CountReadMessages')
            ->select('uc2.LastMessageID', '', 'UserLastMessageID')
            ->from('UserConversation uc')
            ->join('UserConversation uc2', 'uc.ConversationID = uc2.ConversationID and uc.UserID = uc2.UserID')
            ->join('Conversation c', 'c.ConversationID = uc2.ConversationID')
            ->where('uc.UserID', $UserID)
            ->where('uc.Deleted', 0)
            ->orderBy('uc.DateConversationUpdated', 'desc')
            ->limit($Limit, $Offset)
            ->get();

        $Data->datasetType(DATASET_TYPE_ARRAY);
        $Result =& $Data->result();

        // Add some calculated fields.
        foreach ($Result as &$Row) {
            if ($Row['UserLastMessageID']) {
                $Row['LastMessageID'] = $Row['UserLastMessageID'];
            }
            $Row['CountNewMessages'] = $Row['CountMessages'] - $Row['CountReadMessages'];
            unset($Row['UserLastMessageID']);
        }

        // Join the participants.
        $this->joinParticipants($Result);

        // Join in the last message.
        Gdn_DataSet::join(
            $Result,
            array(
                'table' => 'ConversationMessage',
                'prefix' => 'Last',
                'parent' => 'LastMessageID',
                'child' => 'MessageID',
                'InsertUserID', 'DateInserted', 'Body', 'Format')
        );

        return $Data;
    }

    /**
     * Get number of conversations involving current user.
     *
     * @since 2.0.0
     * @access public
     *
     * @param int $ViewingUserID Unique ID of current user.
     * @param array $Wheres SQL conditions.
     * @return int Number of messages.
     */
    public function getCount($ViewingUserID, $Wheres = '') {
        if (is_array($Wheres)) {
            $this->SQL->where($Wheres);
        }

        return $this->SQL
            ->select('uc.UserID', 'count', 'Count')
            ->from('UserConversation uc')
            ->where('uc.UserID', $ViewingUserID)
            ->get()
            ->value('Count', 0);
    }

    /**
     * Get number of conversations that meet criteria.
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
            ->select('ConversationID', 'count', 'Count')
            ->from('Conversation')
            ->get();

        if ($Data->numRows() > 0) {
            return $Data->firstRow()->Count;
        }

        return 0;
    }

    /**
     * Get meta data of a single conversation.
     *
     * @since 2.0.0
     * @access public
     *
     * @param int $ConversationID Unique ID of conversation.
     * @param int $ViewingUserID Unique ID of current user.
     * @return Gdn_DataSet SQL result (single row).
     */
    public function getID($ConversationID, $ViewingUserID = false) {
        // Get the conversation.
        $Conversation = $this->getWhere(array('ConversationID' => $ConversationID))->firstRow(DATASET_TYPE_ARRAY);

        if ($ViewingUserID) {
            $Data = $this->SQL->getWhere(
                'UserConversation',
                array('ConversationID' => $ConversationID, 'UserID' => $ViewingUserID)
            )
                ->firstRow(DATASET_TYPE_ARRAY);

            // Convert the array.
            $UserConversation = arrayTranslate($Data, array('LastMessageID', 'CountReadMessages', 'DateLastViewed', 'Bookmarked'));
            $UserConversation['CountNewMessages'] = $Conversation['CountMessages'] - $Data['CountReadMessages'];
        } else {
            $UserConversation = array('CountNewMessages' => 0, 'CountReadMessages' => $Conversation['CountMessages'], 'DateLastViewed' => $Conversation['DateUpdated']);
        }
        $Conversation = array_merge($Conversation, $UserConversation);
        return (object)$Conversation;
    }

    /**
     * Get all users involved in conversation.
     *
     * @since 2.0.0
     * @access public
     *
     * @param int $ConversationID Unique ID of conversation.
     * @param int $Limit The number of recipients to grab.
     * @return Gdn_DataSet SQL results.
     */
    public function getRecipients($ConversationID, $Limit = 20) {
        $Data = $this->SQL
            ->select('uc.*')
            ->from('UserConversation uc')
            ->where('uc.ConversationID', $ConversationID)
            ->limit($Limit)
            ->get();

        Gdn::userModel()->joinUsers($Data->result(), array('UserID'));
        return $Data;
    }

    public function joinParticipants(&$Data, $Max = 5) {
        // Loop through the data and find the conversations with >= $Max participants.
        $IDs = array();
        foreach ($Data as $Row) {
            if ($Row['CountParticipants'] <= $Max) {
                $IDs[] = $Row['ConversationID'];
            }
        }

        $Users = $this->SQL
            ->select('*')
            ->from('UserConversation uc')
            ->whereIn('uc.ConversationID', $IDs)
            ->get()->resultArray();
        Gdn::userModel()->joinUsers($Users, array('UserID'));

        $Users = Gdn_DataSet::index($Users, array('ConversationID'), array('Unique' => false));


        foreach ($Data as &$Row) {
            $ConversationID = $Row['ConversationID'];
            if (isset($Users[$ConversationID])) {
                $Row['Participants'] = $Users[$ConversationID];
            } else {
                $Row['Participants'] = array();
            }
        }
    }

    /**
     * Figure out whether or not a user is in a conversation.
     * @param int $ConversationID
     * @param int $UserID
     * @return int|bool
     */
    public function inConversation($ConversationID, $UserID) {
        $Row = $this->SQL
            ->getWhere('UserConversation', array('ConversationID' => $ConversationID, 'UserID' => $UserID))
            ->firstRow(DATASET_TYPE_ARRAY);
        if (!$Row) {
            return false;
        } elseif (!$Row['Deleted']) {
            return true;
        } else {
            return (int)$Row['Deleted'];
        }
    }

    public function joinLastMessages(&$Data) {
        // Grab all of the last message IDs.
        $IDs = array();
        foreach ($Data as &$Row) {
            $Row['CountNewMessages'] = $Row['CountMessages'] - $Row['CountReadMessages'];
            if ($Row['UserLastMessageID']) {
                $Row['LastMessageID'] = $Row['UserLastMessageID'];
            }
            $IDs[] = $Row['LastMessageID'];
        }

        $Messages = $this->SQL->whereIn('MessageID', $IDs)->get('ConversationMessage')->resultArray();
        $Messages = Gdn_DataSet::index($Messages, array('MessageID'));

        foreach ($Data as &$Row) {
            $ID = $Row['LastMessageID'];
            if (isset($Messages[$ID])) {
                $M = $Messages[$ID];
                $Row['LastUserID'] = $M['InsertUserID'];
                $Row['DateLastMessage'] = $M['DateInserted'];
                $Row['LastMessage'] = $M['Body'];
                $Row['Format'] = $M['Format'];

            } else {
                $Row['LastMessageUserID'] = $Row['InsertUserID'];
                $Row['DateLastMessage'] = $Row['DateInserted'];
                $Row['LastMessage'] = null;
                $Row['Format'] = null;
            }
        }

        Gdn::userModel()->joinUsers($Data, array('LastUserID'));
    }


    /**
     * Gets a nice title to represent the participants in a conversation.
     *
     * @param array|object $Conversation
     * @param array|object $Participants
     * @return string Returns a title for the conversation.
     */
    public static function participantTitle($Conversation, $Html = true, $Max = 3) {
        $Participants = val('Participants', $Conversation);
        $Total = (int)val('CountParticipants', $Conversation);
        $MyID = Gdn::session()->UserID;
        $FoundMe = false;

        // Try getting people that haven't left the conversation and aren't you.
        $Users = array();
        $i = 0;
        foreach ($Participants as $Row) {
            if (val('UserID', $Row) == $MyID) {
                $FoundMe = true;
                continue;
            }
            if (val('Deleted', $Row)) {
                continue;
            }
            if ($Html) {
                $Users[] = userAnchor($Row);
            } else {
                $Users[] = val('Name', $Row);
            }

            $i++;
            if ($i > $Max || ($Total > $Max && $i === $Max)) {
                break;
            }
        }

        $Count = count($Users);

        if ($Count === 0) {
            if ($FoundMe) {
                $Result = t('Just you');
            } elseif ($Total)
                $Result = plural($Total, '%s person', '%s people');
            else {
                $Result = t('Nobody');
            }
        } else {
            $Px = implode(', ', $Users);

            if ($Count + 1 === $Total && $FoundMe) {
                $Result = $Px;
            } elseif ($Total - $Count === 1) {
                $Result = sprintf(t('%s and 1 other'), $Px);
            } elseif ($Total > $Count) {
                $Result = sprintf(t('%s and %s others'), $Px, $Total - $Count);
            } else {
                $Result = $Px;
            }
        }

        return $Result;
    }

    /**
     * Save conversation from form submission.
     *
     * @since 2.0.0
     * @access public
     *
     * @param array $FormPostValues Values submitted via form.
     * @param ConversationMessageModel $MessageModel Message starting the conversation.
     * @return int Unique ID of conversation created or updated.
     */
    public function save($FormPostValues, $MessageModel) {
        // Define the primary key in this model's table.
        $this->defineSchema();
        $MessageModel->defineSchema();

        $this->EventArguments['FormPostValues'] = $FormPostValues;
        $this->fireEvent('BeforeSaveValidation');

        if (!val('RecipientUserID', $FormPostValues) && isset($FormPostValues['To'])) {
            $To = explode(',', $FormPostValues['To']);
            $To = array_map('trim', $To);

            $RecipientUserIDs = $this->SQL
                ->select('UserID')
                ->from('User')
                ->whereIn('Name', $To)
                ->get()->resultArray();
            $RecipientUserIDs = array_column($RecipientUserIDs, 'UserID');
            $FormPostValues['RecipientUserID'] = $RecipientUserIDs;
        }

        if (c('Garden.ForceInputFormatter')) {
            $FormPostValues['Format'] = c('Garden.InputFormatter');
        }

        // Add & apply any extra validation rules:
        $this->Validation->applyRule('Body', 'Required');
        $MessageModel->Validation->applyRule('Body', 'Required');
        // Make sure that there is at least one recipient
        $this->Validation->addRule('OneOrMoreArrayItemRequired', 'function:ValidateOneOrMoreArrayItemRequired');
        $this->Validation->applyRule('RecipientUserID', 'OneOrMoreArrayItemRequired');

        // Add insert/update fields
        $this->addInsertFields($FormPostValues);
        $this->addUpdateFields($FormPostValues);

        // Validate the form posted values
        $ConversationID = false;
        if ($this->validate($FormPostValues)
            && $MessageModel->validate($FormPostValues)
            && !$this->checkForSpam('Conversation')
        ) {
            $Fields = $this->Validation->validationFields(); // All fields on the form that relate to the schema


            // Define the recipients, and make sure that the sender is in the list
            $RecipientUserIDs = val('RecipientUserID', $Fields, 0);

            if (!in_array($FormPostValues['InsertUserID'], $RecipientUserIDs)) {
                $RecipientUserIDs[] = $FormPostValues['InsertUserID'];
            }

            // Also make sure there are no duplicates in the recipient list
            $RecipientUserIDs = array_unique($RecipientUserIDs);
            sort($RecipientUserIDs);
            $Fields = $this->Validation->schemaValidationFields(); // All fields on the form that relate to the schema
            $ConversationID = $this->SQL->insert($this->Name, $Fields);
            $FormPostValues['ConversationID'] = $ConversationID;

            // Notify the message model that it's being called as a direct result
            // of a new conversation being created. As of now, this is being used
            // so that spam checks between new conversations and conversation
            // messages each have a separate counter. Without this, a new
            // conversation will cause itself AND the message model spam counter
            // to increment by 1.
            $MessageID = $MessageModel->save($FormPostValues, null, array(
                'NewConversation' => true
            ));

            $this->SQL
                ->update('Conversation')
                ->set('FirstMessageID', $MessageID)
                ->where('ConversationID', $ConversationID)
                ->put();

            // Now that the message & conversation have been inserted, insert all of the recipients
            foreach ($RecipientUserIDs as $UserID) {
                $CountReadMessages = $UserID == $FormPostValues['InsertUserID'] ? 1 : 0;
                $this->SQL->options('Ignore', true)->insert('UserConversation', array(
                    'UserID' => $UserID,
                    'ConversationID' => $ConversationID,
                    'LastMessageID' => $MessageID,
                    'CountReadMessages' => $CountReadMessages,
                    'DateConversationUpdated' => $FormPostValues['DateUpdated']
                ));
            }

            // And update the CountUnreadConversations count on each user related to the discussion.
            $this->updateUserUnreadCount(array_diff($RecipientUserIDs, array($FormPostValues['InsertUserID'])));
            $this->updateParticipantCount($ConversationID);

            $this->EventArguments['Recipients'] = $RecipientUserIDs;
            $Conversation = $this->getID($ConversationID);
            $this->EventArguments['Conversation'] = $Conversation;
            $Message = $MessageModel->getID($MessageID, DATASET_TYPE_ARRAY);
            $this->EventArguments['Message'] = $Message;
            $this->fireEvent('AfterAdd');

            // Add notifications (this isn't done by the conversationmessagemodule
            // because the conversation has not yet been created at the time they are
            // inserted)
            $UnreadData = $this->SQL
                ->select('uc.UserID')
                ->from('UserConversation uc')
                ->where('uc.ConversationID', $ConversationID)// hopefully coax this index.
                ->where('uc.UserID <>', $FormPostValues['InsertUserID'])
                ->get();

            $Activity = array(
                'ActivityType' => 'ConversationMessage',
                'ActivityUserID' => $FormPostValues['InsertUserID'],
                'HeadlineFormat' => t('HeadlineFormat.ConversationMessage', '{ActivityUserID,User} sent you a <a href="{Url,html}">message</a>'),
                'RecordType' => 'Conversation',
                'RecordID' => $ConversationID,
                'Story' => val('Body', $FormPostValues),
                'Format' => val('Format', $FormPostValues, c('Garden.InputFormatter')),
                'Route' => "/messages/$ConversationID#Message_$MessageID"
            );

            $Subject = val('Subject', $Fields);
            if ($Subject) {
                $Activity['HeadlineFormat'] = $Subject;
            }

            $ActivityModel = new ActivityModel();
            foreach ($UnreadData->result() as $User) {
                $Activity['NotifyUserID'] = $User->UserID;
                $ActivityModel->queue($Activity, 'ConversationMessage');
            }
            $ActivityModel->saveQueue();

        } else {
            // Make sure that all of the validation results from both validations are present for view by the form
            foreach ($MessageModel->validationResults() as $FieldName => $Results) {
                foreach ($Results as $Result) {
                    $this->Validation->addValidationResult($FieldName, $Result);
                }
            }
        }

        return $ConversationID;
    }

    /**
     * Clear a conversation for a specific user id.
     *
     * @since 2.0.0
     * @access public
     *
     * @param int $ConversationID Unique ID of conversation effected.
     * @param int $ClearingUserID Unique ID of current user.
     */
    public function clear($ConversationID, $ClearingUserID) {
        $this->SQL->update('UserConversation')
            ->set('Deleted', 1)
            ->set('DateLastViewed', Gdn_Format::toDateTime())
            ->where('UserID', $ClearingUserID)
            ->where('ConversationID', $ConversationID)
            ->put();

        $this->countUnread($ClearingUserID);
        $this->updateParticipantCount($ConversationID);
    }

    /**
     * Count unread messages.
     *
     * @param int $UserID Unique ID for user being queried.
     * @param bool $Save Whether to update user record.
     * @return int
     */
    public function countUnread($UserID, $Save = true) {
        // Also update the unread conversation count for this user
        $CountUnread = $this->SQL
            ->select('c.ConversationID', 'count', 'CountUnread')
            ->from('UserConversation uc')
            ->join('Conversation c', 'c.ConversationID = uc.ConversationID and uc.CountReadMessages < c.CountMessages')
            ->where('uc.UserID', $UserID)
            ->where('uc.Deleted', 0)
            ->get()->value('CountUnread', 0);

        if ($Save) {
            Gdn::userModel()->setField($UserID, 'CountUnreadConversations', $CountUnread);
        }

        return $CountUnread;
    }

    /**
     * Update a conversation as read for a specific user id.
     *
     * @since 2.0.0
     * @access public
     *
     * @param int $ConversationID Unique ID of conversation effected.
     * @param int $ReadingUserID Unique ID of current user.
     */
    public function markRead($ConversationID, $ReadingUserID) {
        // Update the the read conversation count for the user.
        $this->SQL->update('UserConversation uc')
            ->join('Conversation c', 'c.ConversationID = uc.ConversationID')
            ->set('uc.CountReadMessages', 'c.CountMessages', false)
            ->set('uc.DateLastViewed', Gdn_Format::toDateTime())
            ->set('uc.LastMessageID', 'c.LastMessageID', false)
            ->where('c.ConversationID', $ConversationID)
            ->where('uc.ConversationID', $ConversationID)
            ->where('uc.UserID', $ReadingUserID)
            ->put();

        // Also update the unread conversation count for this user
        $CountUnread = $this->countUnread($ReadingUserID);

        // Also write through to the current session user.
        if ($ReadingUserID > 0 && $ReadingUserID == Gdn::session()->UserID) {
            Gdn::session()->User->CountUnreadConversations = $CountUnread;
        }
    }

    /**
     * Bookmark (or unbookmark) a conversation for a specific user id.
     *
     * @since 2.0.0
     * @access public
     *
     * @param int $ConversationID Unique ID of conversation effected.
     * @param int $UserID Unique ID of current user.
     * @return bool Whether it is currently bookmarked.
     */
    public function bookmark($ConversationID, $UserID) {
        $Bookmark = false;
        $Discussion = $this->getID($ConversationID, $UserID);
        if (is_object($Discussion)) {
            $Bookmark = $Discussion->Bookmark == '0' ? '1' : '0';
            $this->SQL->update('UserConversation')
                ->set('Bookmark', $Bookmark)
                ->where('ConversationID', $ConversationID)
                ->where('UserID', $UserID)
                ->put();
            $Bookmark == '1' ? true : false;
        }
        return $Bookmark;
    }

    /**
     * Add another user to the conversation.
     *
     * @since 2.0.0
     * @access public
     *
     * @param int $ConversationID Unique ID of conversation effected.
     * @param int $UserID Unique ID of current user.
     */
    public function addUserToConversation($ConversationID, $UserID) {
        if (!is_array($UserID)) {
            $UserID = array($UserID);
        }

        // First define the current users in the conversation
        $OldContributorData = $this->getRecipients($ConversationID);
        $OldContributorData = Gdn_DataSet::index($OldContributorData, 'UserID');
        $AddedUserIDs = array();

        // Get some information about this conversation
        $ConversationData = $this->SQL
            ->select('LastMessageID')
            ->select('DateUpdated')
            ->select('CountMessages')
            ->from('Conversation')
            ->where('ConversationID', $ConversationID)
            ->get()
            ->firstRow();

        // Add the user(s) if they are not already in the conversation
        foreach ($UserID as $NewUserID) {
            if (!array_key_exists($NewUserID, $OldContributorData)) {
                $AddedUserIDs[] = $NewUserID;
                $this->SQL->insert('UserConversation', array(
                    'UserID' => $NewUserID,
                    'ConversationID' => $ConversationID,
                    'LastMessageID' => $ConversationData->LastMessageID,
                    'CountReadMessages' => 0,
                    'DateConversationUpdated' => $ConversationData->DateUpdated
                ));
            } elseif ($OldContributorData[$NewUserID]->Deleted) {
                $AddedUserIDs[] = $NewUserID;

                $this->SQL->put(
                    'UserConversation',
                    array('Deleted' => 0),
                    array('ConversationID' => $ConversationID, 'UserID' => $NewUserID)
                );
            }
        }
        if (count($AddedUserIDs) > 0) {
            $ActivityModel = new ActivityModel();
            foreach ($AddedUserIDs as $AddedUserID) {
                $ActivityModel->queue(
                    array(
                    'ActivityType' => 'AddedToConversation',
                    'NotifyUserID' => $AddedUserID,
                    'HeadlineFormat' => t('You were added to a conversation.', '{ActivityUserID,User} added you to a <a href="{Url,htmlencode}">conversation</a>.'),
                    'Route' => '/messages/'.$ConversationID
                    ),
                    'ConversationMessage'
                );
            }
            $ActivityModel->saveQueue();

            $this->updateUserUnreadCount($AddedUserIDs);
            $this->updateParticipantCount($ConversationID);
        }
    }

    /**
     * Are we allowed to add more recipients?
     *
     * If we pass $CountRecipients then $ConversationID isn't needed (set to zero).
     *
     * @param int $ConversationID Unique ID of the conversation.
     * @param int $CountRecipients Optionally skip needing to query the count by passing it.
     * @return bool Whether user may add more recipients to conversation.
     */
    public function addUserAllowed($ConversationID = 0, $CountRecipients = 0) {
        // Determine whether recipients can be added
        $CanAddRecipients = true;
        $MaxCount = c('Conversations.MaxRecipients');

        // Avoid a query if we already know we can add. MaxRecipients being unset means unlimited.
        if ($MaxCount && !checkPermission('Garden.Moderation.Manage')) {
            if (!$CountRecipients) {
                // Count current recipients
                $ConversationModel = new ConversationModel();
                $CountRecipients = $ConversationModel->getRecipients($ConversationID);
            }

            // Add 1 because sender counts as a recipient.
            $CanAddRecipients = (count($CountRecipients) < ($MaxCount + 1));
        }

        return $CanAddRecipients;
    }

    /**
     * Update the count of participants.
     *
     * @param int $ConversationID
     */
    public function updateParticipantCount($ConversationID) {
        if (!$ConversationID) {
            return;
        }

        $Count = $this->SQL
            ->select('uc.UserID', 'count', 'CountParticipants')
            ->from('UserConversation uc')
            ->where('uc.ConversationID', $ConversationID)
            ->where('uc.Deleted', 0)
            ->get()->value('CountParticipants', 0);

        $this->setField($ConversationID, 'CountParticipants', $Count);
    }

    /**
     * Update users' unread conversation counter.
     *
     * @param array $UserIDs Array of ints.
     * @param bool $SkipSelf Whether to omit current user.
     */
    public function updateUserUnreadCount($UserIDs, $SkipSelf = false) {

        // Get the current user out of this array
        if ($SkipSelf) {
            $UserIDs = array_diff($UserIDs, array(Gdn::session()->UserID));
        }

        // Update the CountUnreadConversations count on each user related to the discussion.
        $this->SQL
            ->update('User')
            ->set('CountUnreadConversations', 'coalesce(CountUnreadConversations, 0) + 1', false)
            ->whereIn('UserID', $UserIDs)
            ->put();

        // Query it back since it was an expression
        $UserData = $this->SQL
            ->select('UserID')
            ->select('CountUnreadConversations')
            ->from('User')
            ->whereIn('UserID', $UserIDs)
            ->get()->result(DATASET_TYPE_ARRAY);

        // Update the user caches
        foreach ($UserData as $UpdateUser) {
            $UpdateUserID = val('UserID', $UpdateUser);
            $CountUnreadConversations = val('CountUnreadConversations', $UpdateUser);
            $CountUnreadConversations = (is_numeric($CountUnreadConversations)) ? $CountUnreadConversations : 1;
            Gdn::userModel()->updateUserCache($UpdateUserID, 'CountUnreadConversations', $CountUnreadConversations);
        }
    }
}
