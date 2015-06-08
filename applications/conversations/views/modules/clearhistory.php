<?php if (!defined('APPLICATION')) exit();
if ($this->ConversationID > 0)
    echo anchor(t('Delete Conversation'), '/messages/clear/'.$this->ConversationID.'/'.Gdn::session()->TransientKey(), 'Button Danger BigButton ClearConversation');
