<?php
/**
 * Clear History module.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
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
