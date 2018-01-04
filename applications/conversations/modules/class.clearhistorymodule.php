<?php
/**
 * Clear History module.
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Conversations
 * @since 2.0
 */

/**
 * Renders the "Clear Conversation History" button.
 */
class ClearHistoryModule extends Gdn_Module {

    /** @var int */
    protected $ConversationID;

    public function conversationID($conversationID) {
        $this->ConversationID = $conversationID;
    }

    public function assetTarget() {
        return 'Panel';
    }
}
