<?php
/**
 * Messages controller.
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Conversations
 * @since 2.0
 */

/**
 * MessagesController handles displaying lists of conversations and conversation messages.
 */
class MessagesController extends ConversationsController {

    /** @var array Models to include. */
    public $Uses = array('Form', 'ConversationModel', 'ConversationMessageModel');

    /**  @var ConversationModel */
    public $ConversationModel;

    /** @var object A dataset of users taking part in this discussion. Used by $this->Index. */
    public $RecipientData;

    /** @var int The current offset of the paged data set. Defined and used by $this->Index and $this->All. */
    public $Offset;

    /**
     * Highlight route and include JS, CSS, and modules used by all methods.
     *
     * Always called by dispatcher before controller's requested method.
     *
     * @since 2.0.0
     * @access public
     */
    public function initialize() {
        parent::initialize();
        $this->Menu->highlightRoute('/messages/inbox');
        $this->setData('Breadcrumbs', array(array('Name' => t('Inbox'), 'Url' => '/messages/inbox')));
//      $this->addModule('MeModule');
        $this->addModule('SignedInModule');

        if (checkPermission('Conversations.Conversations.Add')) {
            $this->addModule('NewConversationModule');
        }
    }

    /**
     * Start a new conversation.
     *
     * @since 2.0.0
     * @access public
     *
     * @param string $Recipient Username of the recipient.
     */
    public function add($Recipient = '') {
        $this->permission('Conversations.Conversations.Add');
        $this->Form->setModel($this->ConversationModel);

        // Set recipient limit
        if (!checkPermission('Garden.Moderation.Manage') && c('Conversations.MaxRecipients')) {
            $this->addDefinition('MaxRecipients', c('Conversations.MaxRecipients'));
            $this->setData('MaxRecipients', c('Conversations.MaxRecipients'));
        }

        if ($this->Form->authenticatedPostBack()) {
            $RecipientUserIDs = array();
            $To = explode(',', $this->Form->getFormValue('To', ''));
            $UserModel = new UserModel();
            foreach ($To as $Name) {
                if (trim($Name) != '') {
                    $User = $UserModel->getByUsername(trim($Name));
                    if (is_object($User)) {
                        $RecipientUserIDs[] = $User->UserID;
                    }
                }
            }

            // Enforce MaxRecipients
            if (!$this->ConversationModel->addUserAllowed(0, count($RecipientUserIDs))) {
                // Reuse the Info message now as an error.
                $this->Form->addError(sprintf(
                    plural(
                        $this->data('MaxRecipients'),
                        "You are limited to %s recipient.",
                        "You are limited to %s recipients."
                    ),
                    c('Conversations.MaxRecipients')
                ));
            }

            $this->EventArguments['Recipients'] = $RecipientUserIDs;
            $this->fireEvent('BeforeAddConversation');

            $this->Form->setFormValue('RecipientUserID', $RecipientUserIDs);
            $ConversationID = $this->Form->save($this->ConversationMessageModel);
            if ($ConversationID !== false) {
                $Target = $this->Form->getFormValue('Target', 'messages/'.$ConversationID);
                $this->RedirectUrl = url($Target);

                $Conversation = $this->ConversationModel->getID($ConversationID, Gdn::session()->UserID);
                $NewMessageID = val('FirstMessageID', $Conversation);
                $this->EventArguments['MessageID'] = $NewMessageID;
                $this->fireEvent('AfterConversationSave');
            }
        } else {
            if ($Recipient != '') {
                $this->Form->setValue('To', $Recipient);
            }
        }
        if ($Target = Gdn::request()->get('Target')) {
            $this->Form->addHidden('Target', $Target);
        }

        Gdn_Theme::section('PostConversation');
        $this->title(t('New Conversation'));
        $this->setData('Breadcrumbs', array(
            array('Name' => t('Inbox'), 'Url' => '/messages/inbox'),
            array('Name' => $this->data('Title'), 'Url' => 'messages/add')
        ));
        $this->render();
    }

    /**
     * Add a message to a conversation.
     *
     * @since 2.0.0
     * @access public
     *
     * @param int $ConversationID Unique ID of the conversation.
     */
    public function addMessage($ConversationID = '') {
        $this->Form->setModel($this->ConversationMessageModel);
        if (is_numeric($ConversationID) && $ConversationID > 0) {
            $this->Form->addHidden('ConversationID', $ConversationID);
        }

        if ($this->Form->authenticatedPostBack()) {
            $ConversationID = $this->Form->getFormValue('ConversationID', '');

            // Make sure the user posting to the conversation is actually
            // a member of it, or is allowed, like an admin.
            if (!checkPermission('Garden.Moderation.Manage')) {
                $UserID = Gdn::session()->UserID;
                $ValidConversationMember = $this->ConversationModel->validConversationMember($ConversationID, $UserID);
                if (!$ValidConversationMember) {
                    throw permissionException();
                }
            }

            $Conversation = $this->ConversationModel->getID($ConversationID, Gdn::session()->UserID);

            $this->EventArguments['Conversation'] = $Conversation;
            $this->EventArguments['ConversationID'] = $ConversationID;
            $this->fireEvent('BeforeAddMessage');

            $NewMessageID = $this->Form->save();

            if ($NewMessageID) {
                if ($this->deliveryType() == DELIVERY_TYPE_ALL) {
                    redirect('messages/'.$ConversationID.'/#'.$NewMessageID, 302);
                }

                $this->setJson('MessageID', $NewMessageID);

                $this->EventArguments['MessageID'] = $NewMessageID;
                $this->fireEvent('AfterMessageSave');

                // If this was not a full-page delivery type, return the partial response
                // Load all new messages that the user hasn't seen yet (including theirs)
                $LastMessageID = $this->Form->getFormValue('LastMessageID');
                if (!is_numeric($LastMessageID)) {
                    $LastMessageID = $NewMessageID - 1;
                }

                $Session = Gdn::session();
                $MessageData = $this->ConversationMessageModel->getNew($ConversationID, $LastMessageID);
                $this->Conversation = $Conversation;
                $this->MessageData = $MessageData;
                $this->setData('Messages', $MessageData);

                $this->View = 'messages';
            } else {
                // Handle ajax based errors...
                if ($this->deliveryType() != DELIVERY_TYPE_ALL) {
                    $this->errorMessage($this->Form->errors());
                }
            }
        }
        $this->render();
    }

    /**
     * Show all conversations for the currently authenticated user.
     *
     * @since 2.0.0
     * @access public
     *
     * @param string $Page
     */
    public function all($Page = '') {
        $Session = Gdn::session();
        $this->title(t('Inbox'));
        Gdn_Theme::section('ConversationList');

        list($Offset, $Limit) = offsetLimit($Page, c('Conversations.Conversations.PerPage', 50));

        // Calculate offset
        $this->Offset = $Offset;

        $UserID = $this->Request->get('userid', Gdn::session()->UserID);
        if ($UserID != Gdn::session()->UserID) {
            if (!c('Conversations.Moderation.Allow', false)) {
                throw permissionException();
            }
            $this->permission('Conversations.Moderation.Manage');
        }

        $conversations = $this->ConversationModel->get2($UserID, $Offset, $Limit)->resultArray();

        $this->EventArguments['Conversations'] = &$conversations;
        $this->fireEvent('beforeMessagesAll');

        $this->setData('Conversations', $conversations);

        // Get Conversations Count
        //$CountConversations = $this->ConversationModel->getCount($UserID);
        //$this->setData('CountConversations', $CountConversations);

        // Build the pager
        if (!$this->data('_PagerUrl')) {
            $this->setData('_PagerUrl', 'messages/all/{Page}');
        }
        $this->setData('_Page', $Page);
        $this->setData('_Limit', $Limit);
        $this->setData('_CurrentRecords', count($conversations));

        // Deliver json data if necessary
        if ($this->_DeliveryType != DELIVERY_TYPE_ALL && $this->_DeliveryMethod == DELIVERY_METHOD_XHTML) {
            $this->setJson('LessRow', $this->Pager->toString('less'));
            $this->setJson('MoreRow', $this->Pager->toString('more'));
            $this->View = 'conversations';
        }

        // Build and display page.
        $this->render();
    }

    /**
     * Clear the message history for a specific conversation & user.
     *
     * @since 2.0.0
     * @access public
     *
     * @param int $ConversationID Unique ID of conversation to clear.
     */
    public function clear($ConversationID = false, $TransientKey = '') {
        $Session = Gdn::session();

        // Yes/No response
        $this->_DeliveryType = DELIVERY_TYPE_BOOL;

        $ValidID = (is_numeric($ConversationID) && $ConversationID > 0);
        $ValidSession = ($Session->UserID > 0 && $Session->validateTransientKey($TransientKey));

        if ($ValidID && $ValidSession) {
            // Clear it
            $this->ConversationModel->clear($ConversationID, $Session->UserID);
            $this->informMessage(t('The conversation has been cleared.'));
            $this->RedirectUrl = url('/messages/all');
        }

        $this->render();
    }

    /**
     * Shows all uncleared messages within a conversation for the viewing user
     *
     * @since 2.0.0
     * @access public
     *
     * @param int $ConversationID Unique ID of conversation to view.
     * @param int $Offset Number to skip.
     * @param int $Limit Number to show.
     */
    public function index($ConversationID = false, $Offset = -1, $Limit = '') {
        $this->Offset = $Offset;
        $Session = Gdn::session();
        Gdn_Theme::section('Conversation');

        // Figure out Conversation ID
        if (!is_numeric($ConversationID) || $ConversationID < 0) {
            $ConversationID = 0;
        }

        // Form setup for adding comments
        $this->Form->setModel($this->ConversationMessageModel);
        $this->Form->addHidden('ConversationID', $ConversationID);

        // Check permissions on the recipients.
        $InConversation = $this->ConversationModel->inConversation($ConversationID, Gdn::session()->UserID);

        if (!$InConversation) {
            // Conversation moderation must be enabled and they must have permission
            if (!c('Conversations.Moderation.Allow', false)) {
                throw permissionException();
            }
            $this->permission('Conversations.Moderation.Manage');
        }

        $this->Conversation = $this->ConversationModel->getID($ConversationID);
        $this->Conversation->Participants = $this->ConversationModel->getRecipients($ConversationID);
        $this->setData('Conversation', $this->Conversation);

        // Bad conversation? Redirect
        if ($this->Conversation === false) {
            throw notFoundException('Conversation');
        }

        // Get limit
        if ($Limit == '' || !is_numeric($Limit) || $Limit < 0) {
            $Limit = Gdn::config('Conversations.Messages.PerPage', 50);
        }

        // Calculate counts
        if (!is_numeric($this->Offset) || $this->Offset < 0) {
            // Round down to the appropriate offset based on the user's read messages & messages per page
            $CountReadMessages = $this->Conversation->CountMessages - $this->Conversation->CountNewMessages;
            if ($CountReadMessages < 0) {
                $CountReadMessages = 0;
            }

            if ($CountReadMessages > $this->Conversation->CountMessages) {
                $CountReadMessages = $this->Conversation->CountMessages;
            }

            // (((67 comments / 10 perpage) = 6.7) rounded down = 6) * 10 perpage = offset 60;
            $this->Offset = floor($CountReadMessages / $Limit) * $Limit;

            // Send the hash link in.
            if ($CountReadMessages > 1) {
                $this->addDefinition('LocationHash', '#Item_'.$CountReadMessages);
            }
        }

        // Fetch message data
        $this->MessageData = $this->ConversationMessageModel->get(
            $ConversationID,
            $Session->UserID,
            $this->Offset,
            $Limit
        );

        $this->setData('Messages', $this->MessageData);

        // Figure out who's participating.
        $ParticipantTitle = ConversationModel::participantTitle($this->Conversation, true);
        $this->Participants = $ParticipantTitle;

        $this->title(strip_tags($this->Participants));

        // $CountMessages = $this->ConversationMessageModel->getCount($ConversationID, $Session->UserID);

        // Build a pager
        $PagerFactory = new Gdn_PagerFactory();
        $this->Pager = $PagerFactory->getPager('MorePager', $this);
        $this->Pager->MoreCode = 'Newer Messages';
        $this->Pager->LessCode = 'Older Messages';
        $this->Pager->ClientID = 'Pager';
        $this->Pager->configure(
            $this->Offset,
            $Limit,
            $this->Conversation->CountMessages,
            'messages/'.$ConversationID.'/%1$s/%2$s/'
        );

        // Mark the conversation as ready by this user.
        $this->ConversationModel->markRead($ConversationID, $Session->UserID);

        // Deliver json data if necessary
        if ($this->_DeliveryType != DELIVERY_TYPE_ALL) {
            $this->setJson('LessRow', $this->Pager->toString('less'));
            $this->setJson('MoreRow', $this->Pager->toString('more'));
            $this->View = 'messages';
        }

        // Add modules.
        $ClearHistoryModule = new ClearHistoryModule($this);
        $ClearHistoryModule->conversationID($ConversationID);
        $this->addModule($ClearHistoryModule);

        $InThisConversationModule = new InThisConversationModule($this);
        $InThisConversationModule->setData($this->Conversation->Participants);
        $this->addModule($InThisConversationModule);

        // Doesn't make sense for people who can't even start conversations to be adding people
        if (checkPermission('Conversations.Conversations.Add')) {
            $this->addModule('AddPeopleModule');
        }

        $Subject = $this->data('Conversation.Subject');
        if (!$Subject) {
            $Subject = t('Message');
        }

        $this->Data['Breadcrumbs'][] = array(
            'Name' => $Subject,
            'Url' => url('', '//'));

        // Render view
        $this->render();
    }

    /**
     *
     *
     * @param $ConversationID
     * @param null $LastMessageID
     * @throws Exception
     */
    public function getNew($ConversationID, $LastMessageID = null) {
        $this->RecipientData = $this->ConversationModel->getRecipients($ConversationID);
        $this->setData('Recipients', $this->RecipientData);

        // Check permissions on the recipients.
        $InConversation = false;
        foreach ($this->RecipientData->result() as $Recipient) {
            if ($Recipient->UserID == Gdn::session()->UserID) {
                $InConversation = true;
                break;
            }
        }

        if (!$InConversation) {
            // Conversation moderation must be enabled and they must have permission
            if (!c('Conversations.Moderation.Allow', false)) {
                throw permissionException();
            }
            $this->permission('Conversations.Moderation.Manage');
        }

        $this->Conversation = $this->ConversationModel->getID($ConversationID);
        $this->setData('Conversation', $this->Conversation);

        // Bad conversation? Redirect
        if ($this->Conversation === false) {
            throw notFoundException('Conversation');
        }

        $Where = array();
        if ($LastMessageID) {
            if (strpos($LastMessageID, '_') !== false) {
                $LastMessageID = array_pop(explode('_', $LastMessageID));
            }

            $Where['MessageID >='] = $LastMessageID;
        }

        // Fetch message data
        $this->setData(
            'MessageData',
            $this->ConversationMessageModel->get(
                $ConversationID,
                Gdn::session()->UserID,
                0,
                50,
                $Where
            ),
            true
        );

        $this->render('Messages');
    }

    /**
     *
     */
    public function popin() {
        $this->permission('Garden.SignIn.Allow');

        // Fetch from model
        $Conversations = $this->ConversationModel->get2(
            Gdn::session()->UserID,
            0,
            5
        )->resultArray();

        // Last message user data
        Gdn::userModel()->joinUsers($Conversations, array('LastInsertUserID'));

        $this->EventArguments['Conversations'] = &$Conversations;
        $this->fireEvent('beforeMessagesPopin');

        // Join in the participants.
        $this->setData('Conversations', $Conversations);
        $this->render();
    }

    /**
     * Allows users to bookmark conversations.
     *
     * @since 2.0.0
     * @access public
     *
     * @param int $ConversationID Unique ID of conversation to view.
     * @param string $TransientKey Single-use hash to prove intent.
     */
    public function bookmark($ConversationID = '', $TransientKey = '') {
        $Session = Gdn::session();
        $Success = false;
        $Star = false;

        // Validate & do bookmarking
        if (is_numeric($ConversationID)
            && $ConversationID > 0
            && $Session->UserID > 0
            && $Session->validateTransientKey($TransientKey)
        ) {
            $Bookmark = $this->ConversationModel->bookmark($ConversationID, $Session->UserID);
        }

        // Report success or error
        if ($Bookmark === false) {
            $this->Form->addError('ErrorBool');
        } else {
            $this->setJson('Bookmark', $Bookmark);
        }

        // Redirect back where the user came from if necessary
        if ($this->_DeliveryType == DELIVERY_TYPE_ALL) {
            redirect($_SERVER['HTTP_REFERER']);
        } else {
            $this->render();
        }
    }

    /**
     * Show bookmarked conversations for the current user.
     *
     * @since 2.0.0
     * @access public
     *
     * @param int $Offset Number to skip.
     * @param string $Limit Number to show.
     */
//   public function bookmarked($Offset = 0, $Limit = '') {
//      $this->View = 'All';
//      $this->All($Offset, $Limit, true);
//   }

    /**
     * Show bookmarked conversations for the current user.
     *
     * @since 2.0.0
     * @access public
     *
     * @param int $Offset Number to skip.
     * @param string $Limit Number to show.
     */
    public function inbox($Page = '') {
        $this->View = 'All';
        $this->all($Page);
    }
}
