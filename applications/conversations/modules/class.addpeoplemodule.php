<?php
/**
 * Add People module.
 *
 * @copyright 2008-2015 Vanilla Forums, Inc
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
    public $AddUserAllowed = TRUE;

    /**
     *
     *
     * @param string $Sender
     * @throws Exception
     */
    public function __construct($Sender = '') {
        $Session = Gdn::Session();
        if (property_exists($Sender, 'Conversation'))
            $this->Conversation = $Sender->Conversation;

        // Allowed to use this module?
        $this->AddUserAllowed = $Sender->ConversationModel->AddUserAllowed($this->Conversation->ConversationID);

        $this->Form = Gdn::Factory('Form', 'AddPeople');
        // $this->Form->Action = $Sender->SelfUrl;
        // If the form was posted back, check for people to add to the conversation
        if ($this->Form->AuthenticatedPostBack()) {
            // Defer exceptions until they try to use the form so we don't fill our logs
            if (!$this->AddUserAllowed || !CheckPermission('Conversations.Conversations.Add')) {
                throw PermissionException();
            }

            $NewRecipientUserIDs = array();
            $NewRecipients = explode(',', $this->Form->GetFormValue('AddPeople', ''));
            $UserModel = Gdn::Factory("UserModel");
            foreach ($NewRecipients as $Name) {
                if (trim($Name) != '') {
                    $User = $UserModel->GetByUsername(trim($Name));
                    if (is_object($User))
                        $NewRecipientUserIDs[] = $User->UserID;
                }
            }
            $Sender->ConversationModel->AddUserToConversation($this->Conversation->ConversationID, $NewRecipientUserIDs);
            // if ($Sender->DeliveryType() == DELIVERY_TYPE_ALL)
            //    Redirect('/messages/'.$this->Conversation->ConversationID);

            $Sender->InformMessage(T('Your changes were saved.'));
            $Sender->RedirectUrl = Url('/messages/'.$this->Conversation->ConversationID);
        }
        $this->_ApplicationFolder = $Sender->Application;
        $this->_ThemeFolder = $Sender->Theme;
    }

    /**
     *
     *
     * @return string
     */
    public function AssetTarget() {
        return 'Panel';
    }

    /**
     * Render the module.
     *
     * @return string Rendered HTML.
     */
    public function ToString() {
        // Simplify our permission logic
        $ConversationExists = (is_object($this->Conversation) && $this->Conversation->ConversationID > 0);
        $CanAddUsers = ($this->AddUserAllowed && CheckPermission('Conversations.Conversations.Add'));

        if ($ConversationExists && $CanAddUsers) {
            return parent::ToString();
        }

        return '';
    }
}
