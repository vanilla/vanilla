<?php
/**
 * In This Conversation module.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @package Conversations
 * @since 2.0
 */

/**
 * Renders a list of people in the specified conversation.
 */
class InThisConversationModule extends Gdn_Module {

    /**
     * Where to render by default.
     *
     * @return string
     */
    public function assetTarget() {
        return 'Panel';
    }

    /**
     * Render the module.
     *
     * @return string HTML.
     */
    public function toString() {
        // Verify any participants exist before outputting anything.
        if (count($this->data('Participants'))) {
            return parent::toString();
        }

        return '';
    }
}
