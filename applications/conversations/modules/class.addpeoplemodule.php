<?php
/**
 * Add People module.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @package Conversations
 * @since 2.0
 */

/**
 * Renders a form that allows people to be added to conversations.
 */
class AddPeopleModule extends Gdn_Module {

    /** @var array */
    public $Conversation;

    /** @var Gdn_Form */
    public $Form;

    /** @var bool Whether user is allowed to use this form. */
    public $AddUserAllowed = true;

    /**
     *
     * @param Gdn_Controller $sender
     * @throws Exception
     */
    public function __construct($sender = null) {
        if (property_exists($sender, 'Conversation')) {
            $this->Conversation = $sender->Conversation;
        }

        // Allowed to use this module?
        $this->AddUserAllowed = $sender->ConversationModel->addUserAllowed($this->Conversation->ConversationID);

        $this->Form = Gdn::factory('Form', 'AddPeople');
        // If the form was posted back, check for people to add to the conversation
        if ($this->Form->authenticatedPostBack()) {
            // Defer exceptions until they try to use the form so we don't fill our logs
            if (!$this->AddUserAllowed || !checkPermission('Conversations.Conversations.Add')) {
                throw permissionException();
            }
            $addPeopleFormValue = $this->Form->getFormValue('AddPeople', '');
            $newRecipientUserIDs = empty($addPeopleFormValue) ? [] : explode(',', $addPeopleFormValue);
            $userModel = Gdn::getContainer()->get(UserModel::class);
            // Unset invalid users.
            foreach ($newRecipientUserIDs as $key => $value) {
                if (is_numeric($value)) {
                    $user = $userModel->getID($value);
                    if (!$user) {
                        unset($newRecipientUserIDs[$key]);
                    }
                } else {
                    throw new Gdn_UserException("Invalid recipient.");
                }
            }
            if (empty($newRecipientUserIDs)) {
                $sender->informMessage(t('You must provide at least one recipient.'));
            } elseif ($sender->ConversationModel->addUserToConversation($this->Conversation->ConversationID, $newRecipientUserIDs)) {
                $sender->informMessage(t('Your changes were saved.'));
            } else {
                $maxRecipients = ConversationModel::getMaxRecipients();
                $sender->informMessage(sprintf(
                    plural(
                        $maxRecipients,
                        "You are limited to %s recipient.",
                        "You are limited to %s recipients."
                    ),
                    $maxRecipients
                ));
            }
            $sender->setRedirectTo('/messages/'.$this->Conversation->ConversationID, false);
        }
        $this->_ApplicationFolder = $sender->Application;
        $this->_ThemeFolder = $sender->Theme;
    }

    /**
     *
     *
     * @return string
     */
    public function assetTarget() {
        return 'Panel';
    }

    /**
     * Render the module.
     *
     * @return string Rendered HTML.
     */
    public function toString() {
        // Simplify our permission logic
        $conversationExists = (is_object($this->Conversation) && $this->Conversation->ConversationID > 0);
        $canAddUsers = ($this->AddUserAllowed && checkPermission('Conversations.Conversations.Add'));

        if ($conversationExists && $canAddUsers) {
            return parent::toString();
        }

        return '';
    }
}
