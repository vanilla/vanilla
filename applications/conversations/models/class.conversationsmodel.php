<?php
/**
 * Conversations model.
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Conversations
 * @since 2.0
 */

/**
 * Introduces common methods that child classes can use.
 */
abstract class ConversationsModel extends Gdn_Model {

    /**
     * Class constructor. Defines the related database table name.
     *
     * @since 2.2
     * @access public
     */
    public function __construct($Name = '') {
        parent::__construct($Name);
    }

    /**
     * Checks to see if the user is spamming. Returns TRUE if the user is spamming.
     *
     * Users cannot post more than $SpamCount comments within $SpamTime
     * seconds or their account will be locked for $SpamLock seconds.
     *
     * @since 2.2
     * @return bool Whether spam check is positive (TRUE = spammer).
     */
    public function checkForSpam($Type, $SkipSpamCheck = false) {
        // If spam checking is disabled or user is an admin, skip
        $SpamCheckEnabled = val('SpamCheck', $this, true);
        if ($SkipSpamCheck == true || $SpamCheckEnabled === false || checkPermission('Garden.Moderation.Manage')) {
            return false;
        }

        $Spam = false;

        // Validate $Type
        if (!in_array($Type, array('Conversation', 'ConversationMessage'))) {
            trigger_error(errorMessage(sprintf('Spam check type unknown: %s', $Type), 'ConversationsModel', 'CheckForSpam'), E_USER_ERROR);
        }

        // Get spam config settings
        $SpamCount = c("Conversations.$Type.SpamCount", 1);
        if (!is_numeric($SpamCount) || $SpamCount < 1) {
            $SpamCount = 1; // 1 spam minimum
        }
        $SpamTime = c("Conversations.$Type.SpamTime", 30);
        if (!is_numeric($SpamTime) || $SpamTime < 30) {
            $SpamTime = 30; // 30 second minimum spam span
        }
        $SpamLock = c("Conversations.$Type.SpamLock", 60);
        if (!is_numeric($SpamLock) || $SpamLock < 60) {
            $SpamLock = 60; // 60 second minimum lockout
        }
        // Check for a spam lock first.
        $Now = time();
        $TimeSpamLock = (int)Gdn::session()->getAttribute("Time{$Type}SpamLock", 0);
        $WaitTime = $SpamLock - ($Now - $TimeSpamLock);
        if ($WaitTime > 0) {
            $Spam = true;
            $this->Validation->addValidationResult(
                'Body',
                '@'.sprintf(
                    t('A spam block is now in effect on your account. You must wait at least %3$s seconds before attempting to post again.'),
                    $SpamCount,
                    $SpamTime,
                    $WaitTime
                )
            );
            return $Spam;
        }

        $CountSpamCheck = Gdn::session()->getAttribute('Count'.$Type.'SpamCheck', 0);
        $TimeSpamCheck = (int)Gdn::session()->getAttribute('Time'.$Type.'SpamCheck', 0);
        $SecondsSinceSpamCheck = time() - $TimeSpamCheck;

        // Apply a spam lock if necessary
        $Attributes = array();
        if ($SecondsSinceSpamCheck < $SpamTime && $CountSpamCheck >= $SpamCount) {
            $Spam = true;
            $this->Validation->addValidationResult(
                'Body',
                '@'.sprintf(
                    t('You have posted %1$s times within %2$s seconds. A spam block is now in effect on your account. You must wait at least %3$s seconds before attempting to post again.'),
                    $SpamCount,
                    $SpamTime,
                    $SpamLock
                )
            );

            // Update the 'waiting period' every time they try to post again
            $Attributes["Time{$Type}SpamLock"] = $Now;
            $Attributes['Count'.$Type.'SpamCheck'] = 0;
        } else {
            if ($SecondsSinceSpamCheck > $SpamTime) {
                $Attributes['Count'.$Type.'SpamCheck'] = 1;
                $Attributes['Time'.$Type.'SpamCheck'] = $Now;
            } else {
                $Attributes['Count'.$Type.'SpamCheck'] = $CountSpamCheck + 1;
            }
        }
        // Update the user profile after every comment
        $UserModel = Gdn::userModel();
        if (Gdn::session()->UserID) {
            $UserModel->saveAttribute(Gdn::session()->UserID, $Attributes);
        }

        return $Spam;
    }

    /**
     * Get all the members of a conversation from the $ConversationID.
     *
     * @param int $ConversationID The conversation ID.
     *
     * @return array Array of user IDs.
     */
    public function getConversationMembers($ConversationID) {
        $ConversationMembers = array();

        $UserConversation = new Gdn_Model('UserConversation');
        $UserMembers = $UserConversation->getWhere(array(
            'ConversationID' => $ConversationID
        ))->resultArray();

        if (is_array($UserMembers) && count($UserMembers)) {
            $ConversationMembers = array_column($UserMembers, 'UserID');
        }

        return $ConversationMembers;
    }

    /**
     * Check if user posting to the conversation is already a member.
     *
     * @param int $ConversationID The conversation ID.
     * @param int $UserID The user id.
     *
     * @return bool
     */
    public function validConversationMember($ConversationID, $UserID) {
        $ConversationMembers = $this->getConversationMembers($ConversationID);
        return (in_array($UserID, $ConversationMembers));
    }
}
