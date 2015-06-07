<?php
/**
 * Add People module.
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
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
     *
     * @param string $Sender
     * @throws Exception
     */
    public function __construct($Sender = '') {
        $Session = Gdn::session();
        if (property_exists($Sender, 'Conversation')) {
            $this->Conversation = $Sender->Conversation;
        }

        // Allowed to use this module?
        $this->AddUserAllowed = $Sender->ConversationModel->addUserAllowed($this->Conversation->ConversationID);

        $this->Form = Gdn::factory('Form', 'AddPeople');
        // $this->Form->Action = $Sender->SelfUrl;
        // If the form was posted back, check for people to add to the conversation
        if ($this->Form->authenticatedPostBack()) {
            // Defer exceptions until they try to use the form so we don't fill our logs
            if (!$this->AddUserAllowed || !checkPermission('Conversations.Conversations.Add')) {
                throw permissionException();
            }

            $NewRecipientUserIDs = array();
            $NewRecipients = explode(',', $this->Form->getFormValue('AddPeople', ''));
            $UserModel = Gdn::factory("UserModel");
            foreach ($NewRecipients as $Name) {
                if (trim($Name) != '') {
                    $User = $UserModel->getByUsername(trim($Name));
                    if (is_object($User)) {
                        $NewRecipientUserIDs[] = $User->UserID;
                    }
                }
            }
            $Sender->ConversationModel->addUserToConversation($this->Conversation->ConversationID, $NewRecipientUserIDs);
            // if ($Sender->deliveryType() == DELIVERY_TYPE_ALL)
            //    redirect('/messages/'.$this->Conversation->ConversationID);

            $Sender->informMessage(t('Your changes were saved.'));
            $Sender->RedirectUrl = url('/messages/'.$this->Conversation->ConversationID);
        }
        $this->_ApplicationFolder = $Sender->Application;
        $this->_ThemeFolder = $Sender->Theme;
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
        $ConversationExists = (is_object($this->Conversation) && $this->Conversation->ConversationID > 0);
        $CanAddUsers = ($this->AddUserAllowed && checkPermission('Conversations.Conversations.Add'));

        if ($ConversationExists && $CanAddUsers) {
            return parent::toString();
        }

        return '';
    }
}
