<?php
/**
 * Inbox module.
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Conversations
 * @since 2.0
 */

/**
 * Handles displaying the inbox.
 */
class InboxModule extends Gdn_Module {

    /** @var int */
    public $Limit = 10;

    /** @var int */
    public $UserID = null;

    public function __construct() {
        parent::__construct();
        $this->_ApplicationFolder = 'conversations';
        $this->UserID = Gdn::session()->UserID;
    }

    public function getData() {
        // Fetch from model.
        $Model = new ConversationModel();
        $Result = $Model->get(
            $this->UserID,
            0,
            $this->Limit,
            array()
        );

        // Join in the participants.
        $Model->joinParticipants($Result);
        $this->setData('Conversations', $Result);
    }

    public function toString() {
        if (!Gdn::session()->isValid()) {
            return '';
        }

        if (!$this->data('Conversations')) {
            $this->getData();
        }

        return parent::toString();
    }
}
