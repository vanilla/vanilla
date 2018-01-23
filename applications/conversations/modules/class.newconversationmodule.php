<?php
/**
 * New Conversation module.
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Conversations
 * @since 2.0
 */

/**
 * Renders the "New Conversation" button.
 */
class NewConversationModule extends Gdn_Module {

    public function assetTarget() {
        return 'Panel';
    }
}
