<?php
/**
 * In This Conversation module.
 *
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Conversations
 * @since 2.0
 */

/**
 * Renders a list of people in the specified conversation.
 */
class InThisConversationModule extends Gdn_Module {
    public function setData($name, $value = null) {
        if (is_array($name)) {
            $this->Data = $name;
        }
        return parent::setData($name, $value);
    }

    public function assetTarget() {
        return 'Panel';
    }

    public function toString() {
        if (is_object($this->Data) && $this->Data->numRows() > 0) {
            return parent::toString();
        }

        return '';
    }
}
