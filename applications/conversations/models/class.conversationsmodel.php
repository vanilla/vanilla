<?php
/**
 * Conversations model.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @package Conversations
 * @since 2.0
 */

/**
 * Introduces common methods that child classes can use.
 */
abstract class ConversationsModel extends Gdn_Model {

    use \Vanilla\FloodControlTrait;

    /**
     * @var \Psr\SimpleCache\CacheInterface Object used to store the FloodControl data.
     */
    protected $floodGate;

    /**
     * Class constructor. Defines the related database table name.
     *
     * @since 2.2
     * @access public
     */
    public function __construct($name = '') {
        parent::__construct($name);
        $this->floodGate = FloodControlHelper::configure($this, 'Conversations', $this->Name);
    }

    /**
     * Checks to see if the user is spamming. Returns TRUE if the user is spamming.
     *
     * Users cannot post more than $SpamCount comments within $SpamTime
     * seconds or their account will be locked for $SpamLock seconds.
     *
     * @deprecated
     *
     * @since 2.2
     * @return bool Whether spam check is positive (TRUE = spammer).
     */
    public function checkForSpam($type, $skipSpamCheck = false) {
        deprecated(__CLASS__.' '.__METHOD__, 'FloodControlTrait::checkUserSpamming()');

        if ($skipSpamCheck) {
            return false;
        }

        $session = Gdn::session();

        // Validate $type
        if (!in_array($type, ['Conversation', 'ConversationMessage'])) {
            trigger_error(errorMessage(sprintf('Spam check type unknown: %s', $type), $this->Name, 'checkForSpam'), E_USER_ERROR);
        }

        $storageObject = FloodControlHelper::configure($this, 'Conversations', $type);
        return $this->checkUserSpamming($session->User->UserID, $storageObject);
    }

    /**
     * Get all the members (deleted or no) of a conversation from the $conversationID.
     *
     * @param int|array $conversationID The conversation ID or a where clause for GDN_UserConversation.
     * @param bool $idsOnly The returns only the userIDs or everything from UserConversation.
     * @param bool $limit
     * @param bool $offset
     * @param bool|null $active **true** for active participants, **false** for users who have left the conversation and **null** for everyone.
     *
     * @return array Array of users or userIDs depending on $idsOnly's value.
     */
    public function getConversationMembers($conversationID, $idsOnly = true, $limit = false, $offset = false, $active = null) {
        $conversationMembers = [];

        $userConversation = new Gdn_Model('UserConversation');
        if (is_array($conversationID)) {
            $where = $conversationID;
        } else {
            $where = ['ConversationID' => $conversationID];
        }
        if ($active === true) {
            $where['Deleted'] = 0;
        } elseif ($active === false) {
            $where['Deleted'] = 1;
        }
        $userMembers = $userConversation->getWhere($where, 'UserID', 'asc', $limit, $offset)->resultArray();

        if (is_array($userMembers) && count($userMembers)) {
            if ($idsOnly) {
                $conversationMembers = array_column($userMembers, 'UserID');
            } else {
                $conversationMembers = Gdn_DataSet::index($userMembers, 'UserID');
            }
        }

        return $conversationMembers;
    }

    /**
     * Get the count of all the members of a conversation from the $conversationID.
     *
     * @param int $conversationID The conversation ID.
     * @param bool|null $active **true** for active participants, **false** for users who have left the conversation and **null** for everyone.
     *
     * @return array Array of users or userIDs depending on $idsOnly's value.
     */
    public function getConversationMembersCount($conversationID, $active = null) {
        $userConversation = new Gdn_Model('UserConversation');

        $where = ['ConversationID' => $conversationID];

        if ($active === true) {
            $where['Deleted'] = 0;
        } elseif ($active === false) {
            $where['Deleted'] = 1;
        }

        return $userConversation->getCount($where);
    }

    /**
     * Check if user posting to the conversation is already a member.
     *
     * @param int $conversationID The conversation ID.
     * @param int $userID The user id.
     *
     * @return bool
     */
    public function validConversationMember($conversationID, $userID) {
        $conversationMembers = $this->getConversationMembers($conversationID, true, false, false, true);
        return (in_array($userID, $conversationMembers));
    }

    /**
     * Notify users when a new message is created.
     *
     * @param array|object $conversation
     * @param array|object $message
     * @param array $notifyUserIDs
     * @param array $options
     */
    protected function notifyUsers($conversation, $message, $notifyUserIDs, $options = []) {
        $conversation = (array)$conversation;
        $message = (array)$message;

        $activity = [
            'ActivityType' => 'ConversationMessage',
            'ActivityUserID' => $message['InsertUserID'],
            'HeadlineFormat' => t('HeadlineFormat.ConversationMessage', '{ActivityUserID,User} sent you a <a href="{Url,html}">message</a>'),
            'RecordType' => 'Conversation',
            'RecordID' => $conversation['ConversationID'],
            'Story' => $message['Body'],
            'ActionText' => $options['ActionText'] ?? t('Reply'),
            'Format' => val('Format', $message, c('Garden.InputFormatter')),
            'Route' => $options['Url'] ?? "/messages/{$conversation['ConversationID']}#Message_{$message['MessageID']}"
        ];

        $subject = $conversation['Subject'] ?? '';
        if ($subject) {
            if (empty($options['FirstMessage'])) {
                $subject = sprintf(t('Re: %s'), $subject);
            }
            $options['EmailSubject'] = $subject;
        } else {
            $options = [];
        }

        $activityModel = new ActivityModel();
        foreach ($notifyUserIDs as $userID) {
            if ($message['InsertUserID'] == $userID) {
                continue; // Don't notify self.
            }

            $activity['NotifyUserID'] = $userID;
            $activityModel->queue($activity, 'ConversationMessage', $options);
        }
        $activityModel->saveQueue();
    }
}
