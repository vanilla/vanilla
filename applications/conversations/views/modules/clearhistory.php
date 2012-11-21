<?php if (!defined('APPLICATION')) exit();
if ($this->ConversationID > 0)
   echo Anchor(T('Delete Conversation'), '/messages/clear/'.$this->ConversationID.'/'.Gdn::Session()->TransientKey(), 'Button Danger BigButton ClearConversation');