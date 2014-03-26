<?php if (!defined('APPLICATION')) exit(); ?>
<div class="DataListWrap">
<h1 class="H">
   <?php
   echo $this->Participants;
   
   if ($this->Data('Conversation.Subject')) {
      echo 
         Bullet(' ').
         '<span class="Gloss">' .htmlspecialchars($this->Data('Conversation.Subject')).'</span>';
   }
   ?>
</h1>
<?php

if ($this->Data('Conversation.Type')) {
   $this->FireEvent('Conversation'.str_replace('_', '', $this->Data('Conversation.Type')));
}

if ($this->Data('_HasDeletedUsers')) {
   echo '<div class="Info">', T('One or more users have left this conversation.', 'One or more users have left this conversation. They won\'t receive any more messages unless you add them back in to the conversation.'), '</div>';
}
$this->FireEvent('BeforeConversation');
echo $this->Pager->ToString('less');
?>
<ul class="DataList MessageList Conversation">
   <?php
   $MessagesViewLocation = $this->FetchViewLocation('messages');
   include($MessagesViewLocation);
   ?>
</ul>
</div>
<?php 
echo $this->Pager->ToString();
echo Gdn::Controller()->FetchView('addmessage');
