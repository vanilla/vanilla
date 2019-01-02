<?php
/**
 * New Conversation module.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
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
