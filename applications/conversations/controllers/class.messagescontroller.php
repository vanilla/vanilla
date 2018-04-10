<?php
/**
 * Messages controller.
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Conversations
 * @since 2.0
 */

/**
 * MessagesController handles displaying lists of conversations and conversation messages.
 */
class MessagesController extends ConversationsController {

    /** @var array Models to include. */
    public $Uses = ['Form', 'ConversationModel', 'ConversationMessageModel'];

    /**  @var ConversationModel */
    public $ConversationModel;

    /** @var Gdn_Form $Form */
    public $Form;

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
        $this->setData('Breadcrumbs', [['Name' => t('Inbox'), 'Url' => '/messages/inbox']]);
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
     * @param string $recipient Username of the recipient.
     * @param string $subject Subject of the message.
     */
    public function add($recipient = '', $subject = '') {
        $this->permission('Conversations.Conversations.Add');
        $this->Form->setModel($this->ConversationModel);

        // Detect our recipient limit.
        $maxRecipients = ConversationModel::getMaxRecipients();

        // Set recipient limit for the frontend.
        if ($maxRecipients) {
            $this->addDefinition('MaxRecipients', $maxRecipients);
            $this->setData('MaxRecipients', $maxRecipients);
        }

        // Sending a new conversation.
        if ($this->Form->authenticatedPostBack()) {
            $recipientUserIDs = [];
            $to = explode(',', $this->Form->getFormValue('To', ''));
            $userModel = new UserModel();
            foreach ($to as $name) {
                if (trim($name) != '') {
                    $user = $userModel->getByUsername(trim($name));
                    if (is_object($user)) {
                        $recipientUserIDs[] = $user->UserID;
                    }
                }
            }

            // Enforce MaxRecipients
            if (!$this->ConversationModel->addUserAllowed(0, count($recipientUserIDs))) {
                // Reuse the Info message now as an error.
                $this->Form->addError(sprintf(
                    plural(
                        $this->data('MaxRecipients'),
                        "You are limited to %s recipient.",
                        "You are limited to %s recipients."
                    ),
                    $maxRecipients
                ));
            }

            $this->EventArguments['Recipients'] = $recipientUserIDs;
            $this->fireEvent('BeforeAddConversation');

            $this->Form->setFormValue('RecipientUserID', $recipientUserIDs);
            $conversationID = $this->Form->save();
            if ($conversationID !== false) {
                $target = $this->Form->getFormValue('Target', 'messages/'.$conversationID);
                $this->setRedirectTo($target);

                $conversation = $this->ConversationModel->getID(
                    $conversationID,
                    false,
                    ['viewingUserID' => Gdn::session()->UserID]
                );
                $newMessageID = val('FirstMessageID', $conversation);
                $this->EventArguments['MessageID'] = $newMessageID;
                $this->fireEvent('AfterConversationSave');
            }
        } else {
            // Check if valid user name has been passed.
            if ($recipient != '') {
                if (!Gdn::userModel()->getByUsername($recipient)) {
                    $this->Form->setValidationResults(
                        [
                            'RecipientUserID' => [
                                sprintf(
                                    '"%s" is an unknown username.',
                                    htmlspecialchars($recipient)
                                )
                            ]
                        ]
                    );
                    $recipient = '';
                } else {
                    $this->Form->setValue('To', $recipient);
                }
            }
            if ($subject != '') {
                $this->Form->setValue('Subject', $subject);
            }
        }
        if ($target = Gdn::request()->get('Target')) {
            $this->Form->addHidden('Target', $target);
        }

        Gdn_Theme::section('PostConversation');
        $this->title(t('New Conversation'));
        $this->setData('Breadcrumbs', [
            ['Name' => t('Inbox'), 'Url' => '/messages/inbox'],
            ['Name' => $this->data('Title'), 'Url' => 'messages/add']
        ]);

        $this->CssClass = 'NoPanel';

        $this->render();
    }

    /**
     * Add a message to a conversation.
     *
     * @since 2.0.0
     * @access public
     *
     * @param int $conversationID Unique ID of the conversation.
     */
    public function addMessage($conversationID = '') {
        $this->Form->setModel($this->ConversationMessageModel);
        if (is_numeric($conversationID) && $conversationID > 0) {
            $this->Form->addHidden('ConversationID', $conversationID);
        }

        if ($this->Form->authenticatedPostBack()) {
            $conversationID = $this->Form->getFormValue('ConversationID', '');

            // Make sure the user posting to the conversation is actually
            // a member of it, or is allowed, like an admin.
            if (!checkPermission('Garden.Moderation.Manage')) {
                $userID = Gdn::session()->UserID;
                $validConversationMember = $this->ConversationModel->validConversationMember($conversationID, $userID);
                if (!$validConversationMember) {
                    throw permissionException();
                }
            }

            $conversation = $this->ConversationModel->getID(
                $conversationID,
                false,
                ['viewingUserID' => Gdn::session()->UserID]
            );

            $this->EventArguments['Conversation'] = $conversation;
            $this->EventArguments['ConversationID'] = $conversationID;
            $this->fireEvent('BeforeAddMessage');

            $newMessageID = $this->Form->save();

            if ($newMessageID) {
                if ($this->deliveryType() == DELIVERY_TYPE_ALL) {
                    redirectTo('messages/'.$conversationID.'/#'.$newMessageID);
                }

                $this->setJson('MessageID', $newMessageID);

                $this->EventArguments['MessageID'] = $newMessageID;
                $this->fireEvent('AfterMessageSave');

                // If this was not a full-page delivery type, return the partial response
                // Load all new messages that the user hasn't seen yet (including theirs)
                $lastMessageID = $this->Form->getFormValue('LastMessageID');
                if (!is_numeric($lastMessageID)) {
                    $lastMessageID = $newMessageID - 1;
                }

                $messageData = $this->ConversationMessageModel->getNew($conversationID, $lastMessageID);
                $this->Conversation = $conversation;
                $this->MessageData = $messageData;
                $this->setData('Messages', $messageData);

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
     * @param string $page The page number argument.
     */
    public function all($page = '') {
        $this->title(t('Inbox'));
        Gdn_Theme::section('ConversationList');

        list($offset, $limit) = offsetLimit($page, c('Conversations.Conversations.PerPage', 50));

        // Calculate offset
        $this->Offset = $offset;

        $userID = $this->Request->get('userid', Gdn::session()->UserID);
        if ($userID != Gdn::session()->UserID) {
            if (!c('Conversations.Moderation.Allow', false)) {
                throw permissionException();
            }
            $this->permission('Conversations.Moderation.Manage');
        }

        $conversations = $this->ConversationModel->get2($userID, $offset, $limit)->resultArray();

        $this->EventArguments['Conversations'] = &$conversations;
        $this->fireEvent('beforeMessagesAll');

        $this->setData('Conversations', $conversations);

        // Build the pager
        if (!$this->data('_PagerUrl')) {
            $this->setData('_PagerUrl', 'messages/all/{Page}');
        }
        $this->setData('_Page', $page);
        $this->setData('_Limit', $limit);
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
     * @param int $conversationID Unique ID of conversation to clear.
     * @param string $transientKey The CSRF token.
     */
    public function clear($conversationID = false, $transientKey = '') {
        deprecated('/messages/clear', '/messages/leave');
        $session = Gdn::session();

        // Yes/No response
        $this->_DeliveryType = DELIVERY_TYPE_BOOL;

        $validID = (is_numeric($conversationID) && $conversationID > 0);
        $validSession = ($session->UserID > 0 && $session->validateTransientKey($transientKey));

        if ($validID && $validSession) {
            // Clear it
            $this->ConversationModel->clear($conversationID, $session->UserID);
            $this->informMessage(t('The conversation has been cleared.'));
            $this->setRedirectTo('/messages/all');
        }

        $this->render();
    }

    /**
     * Leave a conversation that a user is participating in.
     *
     * @param int $conversationID The ID of the conversation to leave.
     */
    public function leave($conversationID) {
        if (!Gdn::session()->UserID) {
            throw new Gdn_UserException('You must be signed in.', 403);
        }

        // Make sure the user has participated in the conversation before.
        $row = Gdn::sql()->getWhere(
            'UserConversation',
            ['ConversationID' => $conversationID, 'UserID' => Gdn::session()->UserID]
        )->firstRow();

        if (!$row) {
            throw notFoundException('Conversation');
        }

        if ($this->Form->authenticatedPostBack(true)) {
            $this->ConversationModel->clear($conversationID, Gdn::session()->UserID);
            $this->setRedirectTo('/messages/all');
        }

        $this->title(t('Leave Conversation'));
        $this->render();
    }

    /**
     * Shows all uncleared messages within a conversation for the viewing user
     *
     * @since 2.0.0
     * @access public
     *
     * @param int $conversationID Unique ID of conversation to view.
     * @param int $offset Number to skip.
     * @param int $limit Number to show.
     */
    public function index($conversationID = false, $offset = -1, $limit = '') {
        $this->Offset = $offset;
        $session = Gdn::session();
        Gdn_Theme::section('Conversation');

        // Figure out Conversation ID
        if (!is_numeric($conversationID) || $conversationID < 0) {
            $conversationID = 0;
        }

        // Form setup for adding comments
        $this->Form->setModel($this->ConversationMessageModel);
        $this->Form->addHidden('ConversationID', $conversationID);

        // Check permissions on the recipients.
        $inConversation = $this->ConversationModel->inConversation($conversationID, Gdn::session()->UserID);

        if (!$inConversation) {
            // Conversation moderation must be enabled and they must have permission
            if (!c('Conversations.Moderation.Allow', false)) {
                throw permissionException();
            }
            $this->permission('Conversations.Moderation.Manage');
        }

        $this->Conversation = $this->ConversationModel->getID($conversationID);
        $this->Conversation->Participants = $this->ConversationModel->getRecipients($conversationID);
        $this->setData('Conversation', $this->Conversation);

        // Bad conversation? Redirect
        if ($this->Conversation === false) {
            throw notFoundException('Conversation');
        }

        // Get limit
        if ($limit == '' || !is_numeric($limit) || $limit < 0) {
            $limit = c('Conversations.Messages.PerPage', 50);
        }
        $limit = (int)$limit;

        // Calculate counts
        if (!is_numeric($this->Offset) || $this->Offset < 0) {
            // Round down to the appropriate offset based on the user's read messages & messages per page
            $countReadMessages = $this->Conversation->CountMessages - $this->Conversation->CountNewMessages;
            if ($countReadMessages < 0) {
                $countReadMessages = 0;
            }

            if ($countReadMessages > $this->Conversation->CountMessages) {
                $countReadMessages = $this->Conversation->CountMessages;
            }

            // (((67 comments / 10 perpage) = 6.7) rounded down = 6) * 10 perpage = offset 60;
            $this->Offset = (int)(($countReadMessages - 1) / $limit) * $limit;

            // Send the hash link in.
            if ($countReadMessages > 1) {
                $this->addDefinition('LocationHash', '#Item_'.$countReadMessages);
            }
        }

        // Fetch message data
        $this->MessageData = $this->ConversationMessageModel->getRecent(
            $conversationID,
            $session->UserID,
            $this->Offset,
            $limit
        );

        $this->EventArguments['MessageData'] = $this->MessageData;
        $this->fireEvent('beforeMessages');

        $this->setData('Messages', $this->MessageData);

        // Figure out who's participating.
        $participantTitle = ConversationModel::participantTitle($this->Conversation, true);
        $this->Participants = $participantTitle;

        $this->title(strip_tags($this->Participants));

        // Build a pager
        $pagerFactory = new Gdn_PagerFactory();
        $this->Pager = $pagerFactory->getPager('MorePager', $this);
        $this->Pager->MoreCode = 'Newer Messages';
        $this->Pager->LessCode = 'Older Messages';
        $this->Pager->ClientID = 'Pager';
        $this->Pager->configure(
            $this->Offset,
            $limit,
            $this->Conversation->CountMessages,
            'messages/'.$conversationID.'/%1$s/%2$s/'
        );

        // Mark the conversation as ready by this user.
        $this->ConversationModel->markRead($conversationID, $session->UserID);

        // Deliver json data if necessary
        if ($this->_DeliveryType != DELIVERY_TYPE_ALL) {
            $this->setJson('LessRow', $this->Pager->toString('less'));
            $this->setJson('MoreRow', $this->Pager->toString('more'));
            $this->View = 'messages';
        }

        // Add modules.
        $clearHistoryModule = new ClearHistoryModule($this);
        $clearHistoryModule->conversationID($conversationID);
        $this->addModule($clearHistoryModule);

        $inThisConversationModule = new InThisConversationModule($this);
        $inThisConversationModule->setData('Participants', $this->Conversation->Participants);
        $this->addModule($inThisConversationModule);

        // Doesn't make sense for people who can't even start conversations to be adding people
        if (checkPermission('Conversations.Conversations.Add')) {
            $this->addModule('AddPeopleModule');
        }

        $subject = $this->data('Conversation.Subject');
        if (!$subject) {
            $subject = t('Message');
        }

        $this->Data['Breadcrumbs'][] = [
            'Name' => $subject,
            'Url' => url('', '//')];

        // Render view
        $this->render();
    }

    /**
     *
     *
     * @param $conversationID
     * @param null $lastMessageID
     * @throws Exception
     */
    public function getNew($conversationID, $lastMessageID = null) {
        $this->RecipientData = $this->ConversationModel->getRecipients($conversationID);
        $this->setData('Recipients', $this->RecipientData);

        // Check permissions on the recipients.
        $inConversation = false;
        foreach ($this->RecipientData->result() as $recipient) {
            if ($recipient->UserID == Gdn::session()->UserID) {
                $inConversation = true;
                break;
            }
        }

        if (!$inConversation) {
            // Conversation moderation must be enabled and they must have permission
            if (!c('Conversations.Moderation.Allow', false)) {
                throw permissionException();
            }
            $this->permission('Conversations.Moderation.Manage');
        }

        $this->Conversation = $this->ConversationModel->getID($conversationID);
        $this->setData('Conversation', $this->Conversation);

        // Bad conversation? Redirect
        if ($this->Conversation === false) {
            throw notFoundException('Conversation');
        }

        $where = [];
        if ($lastMessageID) {
            if (strrpos($lastMessageID, '_') !== false) {
                $lastMessageID = trim(strrchr($lastMessageID, '_'), '_');
            }

            $where['MessageID >='] = $lastMessageID;
        }

        // Fetch message data
        $this->setData(
            'MessageData',
            $this->ConversationMessageModel->getRecent(
                $conversationID,
                Gdn::session()->UserID,
                0,
                50,
                $where
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
        $conversations = $this->ConversationModel->get2(
            Gdn::session()->UserID,
            0,
            5
        )->resultArray();

        // Last message user data
        Gdn::userModel()->joinUsers($conversations, ['LastInsertUserID']);

        $this->EventArguments['Conversations'] = &$conversations;
        $this->fireEvent('beforeMessagesPopin');

        // Join in the participants.
        $this->setData('Conversations', $conversations);
        $this->render();
    }

    /**
     * Allows users to bookmark conversations.
     *
     * @param int $conversationID Unique ID of conversation to view.
     * @param string $transientKey Single-use hash to prove intent.
     */
    public function bookmark($conversationID = '', $transientKey = '') {
        $session = Gdn::session();
        $bookmark = null;

        // Validate & do bookmarking.
        if (is_numeric($conversationID)
            && $conversationID > 0
            && $session->UserID > 0
            && $session->validateTransientKey($transientKey)
        ) {
            $bookmark = $this->ConversationModel->bookmark($conversationID, $session->UserID);
        }

        // Report success or error
        if ($bookmark === false) {
            $this->Form->addError('ErrorBool');
        } else {
            $this->setJson('Bookmark', $bookmark);
        }

        // Redirect back where the user came from if necessary
        if ($this->_DeliveryType == DELIVERY_TYPE_ALL) {
            redirectTo($_SERVER['HTTP_REFERER']);
        } else {
            $this->render();
        }
    }

    /**
     * Show bookmarked conversations for the current user.
     *
     * @param string $page The page number string.
     */
    public function inbox($page = '') {
        $this->View = 'All';
        $this->all($page);
    }
}
