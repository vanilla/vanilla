<?php
/**
 * Clear History module.
 *
 * @copyright 2008-2015 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Coversations
 * @since 2.0
 */

/**
 * Renders the "Clear Conversation History" button.
 */
class ClearHistoryModule extends Gdn_Module {

    /** @var int */
    protected $ConversationID;

    public function ConversationID($ConversationID) {
        $this->ConversationID = $ConversationID;
    }

    public function AssetTarget() {
        return 'Panel';
    }

}
