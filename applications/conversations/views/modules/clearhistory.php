<?php if (!defined('APPLICATION')) exit();
if ($this->ConversationID > 0)
   echo Anchor(T('Clear Conversation History'), '/messages/clear/'.$this->ConversationID, 'ClearConversation');