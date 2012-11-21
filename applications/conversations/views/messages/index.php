<?php if (!defined('APPLICATION')) exit(); ?>
<h1 class="H"><?php echo $this->Participants; ?></h1>
<?php
if ($this->Data('Conversation.Subject') && C('Conversations.Subjects.Visible')) {
   echo '<h2 class="Subject">'.htmlspecialchars($this->Data('Conversation.Subject')).'</h2>';
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
<?php 
echo $this->Pager->ToString(); 
echo Gdn::Controller()->FetchView('addmessage');