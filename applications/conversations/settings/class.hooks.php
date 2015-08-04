<?php
/**
 * Hooks for Conversations.
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Conversations
 * @since 2.0
 */

/**
 * Handles hooks into Dashboard and Vanilla.
 */
class ConversationsHooks implements Gdn_IPlugin {

    /**
     *
     *
     * @param DbaController $Sender
     */
    public function dbaController_countJobs_Handler($Sender) {
        $Counts = array(
            'Conversation' => array('CountMessages', 'CountParticipants', 'FirstMessageID', 'LastMessageID', 'DateUpdated', 'UpdateUserID')
//          'Category' => array('CountDiscussions', 'CountComments', 'LastDiscussionID', 'LastCommentID')
        );

        foreach ($Counts as $Table => $Columns) {
            foreach ($Columns as $Column) {
                $Name = "Recalculate $Table.$Column";
                $Url = "/dba/counts.json?".http_build_query(array('table' => $Table, 'column' => $Column));

                $Sender->Data['Jobs'][$Name] = $Url;
            }
        }
    }

    /**
     * Remove data when deleting a user.
     *
     * @since 2.0.0
     * @access public
     */
    public function userModel_beforeDeleteUser_handler($Sender) {
        $UserID = val('UserID', $Sender->EventArguments);
        $Options = val('Options', $Sender->EventArguments, array());
        $Options = is_array($Options) ? $Options : array();

        $DeleteMethod = val('DeleteMethod', $Options, 'delete');
        if ($DeleteMethod == 'delete') {
            $Sender->SQL->delete('Conversation', array('InsertUserID' => $UserID));
            $Sender->SQL->delete('Conversation', array('UpdateUserID' => $UserID));
            $Sender->SQL->delete('UserConversation', array('UserID' => $UserID));
            $Sender->SQL->delete('ConversationMessage', array('InsertUserID' => $UserID));
        } elseif ($DeleteMethod == 'wipe') {
            $Sender->SQL->update('ConversationMessage')
                ->set('Body', t('The user and all related content has been deleted.'))
                ->set('Format', 'Deleted')
                ->where('InsertUserID', $UserID)
                ->put();
        } else {
            // Leave conversation messages
        }
        // Remove the user's profile information related to this application
        $Sender->SQL->update('User')
            ->set('CountUnreadConversations', 0)
            ->where('UserID', $UserID)
            ->put();
    }

    /**
     * Add 'Inbox' to profile menu.
     *
     * @since 2.0.0
     * @access public
     */
    public function profileController_addProfileTabs_handler($Sender) {
        if (Gdn::session()->isValid()) {
            $Inbox = t('Inbox');
            $InboxHtml = sprite('SpInbox').' '.$Inbox;
            $InboxLink = '/messages/all';

            if (Gdn::session()->UserID != $Sender->User->UserID) {
                // Accomodate admin access
                if (c('Conversations.Moderation.Allow', false) && Gdn::session()->checkPermission('Conversations.Moderation.Manage')) {
                    $CountUnread = $Sender->User->CountUnreadConversations;
                    $InboxLink .= "?userid={$Sender->User->UserID}";
                } else {
                    return;
                }
            } else {
                // Current user
                $CountUnread = Gdn::session()->User->CountUnreadConversations;
            }

            if (is_numeric($CountUnread) && $CountUnread > 0) {
                $InboxHtml .= ' <span class="Aside"><span class="Count">'.$CountUnread.'</span></span>';
            }
            $Sender->addProfileTab($Inbox, $InboxLink, 'Inbox', $InboxHtml);
        }
    }

    /**
     * Add "Message" option to profile options.
     */
    public function profileController_beforeProfileOptions_handler($Sender, $Args) {
        if (!$Sender->EditMode && Gdn::session()->isValid() && Gdn::session()->UserID != $Sender->User->UserID) {
            $Sender->EventArguments['MemberOptions'][] = array(
                'Text' => sprite('SpMessage').' '.t('Message'),
                'Url' => '/messages/add/'.$Sender->User->Name,
                'CssClass' => 'MessageUser'
            );
        }
    }


    /**
     * Additional options for the Preferences screen.
     *
     * @since 2.0.0
     * @access public
     */
    public function profileController_afterPreferencesDefined_handler($Sender) {
        $Sender->Preferences['Notifications']['Email.ConversationMessage'] = t('Notify me of private messages.');
        $Sender->Preferences['Notifications']['Popup.ConversationMessage'] = t('Notify me of private messages.');
    }

    /**
     * Add 'Inbox' to global menu.
     *
     * @since 2.0.0
     * @access public
     */
    public function base_render_before($Sender) {
        // Add the menu options for conversations
        if ($Sender->Menu && Gdn::session()->isValid()) {
            $Inbox = t('Inbox');
            $CountUnreadConversations = val('CountUnreadConversations', Gdn::session()->User);
            if (is_numeric($CountUnreadConversations) && $CountUnreadConversations > 0) {
                $Inbox .= ' <span class="Alert">'.$CountUnreadConversations.'</span>';
            }

            $Sender->Menu->addLink('Conversations', $Inbox, '/messages/all', false, array('Standard' => true));
        }
    }

    /**
     * Let us add Messages to the Inbox page.
     */
    public function base_afterGetLocationData_handler($Sender, $Args) {
        $Args['ControllerData']['Conversations/messages/inbox'] = t('Inbox Page');
    }

    /**
     * Provide default permissions for roles, based on the value in their Type column.
     *
     * @param PermissionModel $Sender Instance of permission model that fired the event
     */
    public function permissionModel_defaultPermissions_handler($Sender) {
        $Sender->addDefault(
            RoleModel::TYPE_MEMBER,
            array('Conversations.Conversations.Add' => 1)
        );
        $Sender->addDefault(
            RoleModel::TYPE_MODERATOR,
            array('Conversations.Conversations.Add' => 1)
        );
        $Sender->addDefault(
            RoleModel::TYPE_ADMINISTRATOR,
            array('Conversations.Conversations.Add' => 1)
        );
    }

    /**
     * Load some information into the BuzzData collection (for Dashboard report).
     *
     * @since 2.0.?
     * @access public
     */
    //public function settingsController_dashboardData_handler($Sender) {
        /*
        $ConversationModel = new ConversationModel();
        // Number of Conversations
        $CountConversations = $ConversationModel->getCountWhere();
        $Sender->addDefinition('CountConversations', $CountConversations);
        $Sender->BuzzData[T('Conversations')] = number_format($CountConversations);
        // Number of New Conversations in the last day
        $Sender->BuzzData[T('New conversations in the last day')] = number_format($ConversationModel->getCountWhere(array('DateInserted >=' => Gdn_Format::toDateTime(strtotime('-1 day')))));
        // Number of New Conversations in the last week
        $Sender->BuzzData[T('New conversations in the last week')] = number_format($ConversationModel->getCountWhere(array('DateInserted >=' => Gdn_Format::toDateTime(strtotime('-1 week')))));

        $ConversationMessageModel = new ConversationMessageModel();
        // Number of Messages
        $CountMessages = $ConversationMessageModel->getCountWhere();
        $Sender->addDefinition('CountConversationMessages', $CountMessages);
        $Sender->BuzzData[T('Conversation Messages')] = number_format($CountMessages);
        // Number of New Messages in the last day
        $Sender->BuzzData[T('New messages in the last day')] = number_format($ConversationMessageModel->getCountWhere(array('DateInserted >=' => Gdn_Format::toDateTime(strtotime('-1 day')))));
        // Number of New Messages in the last week
        $Sender->BuzzData[T('New messages in the last week')] = number_format($ConversationMessageModel->getCountWhere(array('DateInserted >=' => Gdn_Format::toDateTime(strtotime('-1 week')))));
        */
    //}

    /**
     * Database & config changes to be done upon enable.
     *
     * @since 2.0.0
     * @access public
     */
    public function setup() {
        $Database = Gdn::database();
        $Config = Gdn::factory(Gdn::AliasConfig);
        $Drop = false; //C('Conversations.Version') === FALSE ? TRUE : FALSE;
        $Explicit = true;
        $Validation = new Gdn_Validation(); // This is going to be needed by structure.php to validate permission names
        include(PATH_APPLICATIONS.DS.'conversations'.DS.'settings'.DS.'structure.php');
        include(PATH_APPLICATIONS.DS.'conversations'.DS.'settings'.DS.'stub.php');

        $ApplicationInfo = array();
        include(combinePaths(array(PATH_APPLICATIONS.DS.'conversations'.DS.'settings'.DS.'about.php')));
        $Version = arrayValue('Version', arrayValue('Conversations', $ApplicationInfo, array()), 'Undefined');
        saveToConfig('Conversations.Version', $Version);
    }
}
