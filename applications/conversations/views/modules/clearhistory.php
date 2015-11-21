<?php if (!defined('APPLICATION')) exit();
if ($this->ConversationID > 0)
    echo anchor(t('Leave Conversation'), '/messages/leave/'.$this->ConversationID, 'Button Danger BigButton Popup');
