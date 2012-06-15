<?php if (!defined('APPLICATION')) exit(); ?>
<h1 class="H"><?php echo $this->Participants; ?></h1>
<?php
if ($this->Data('Conversation.Subject') && C('Conversations.Subjects.Visible')) {
   echo '<h2>'.htmlspecialchars($this->Data('Conversation.Subject')).'</h2>';
}

if ($this->Data('_HasDeletedUsers')) {
   echo '<div class="Info">', T('One or more users have left this conversation.', 'One or more users have left this conversation. They won\'t receive any more messages unless you add them back in to the conversation.'), '</div>';
}
?>
<!--
<div class="Tabs HeadingTabs ConversationTabs">
   <ul>
      <li><?php echo Anchor(T('Inbox'), '/messages/inbox'); ?></li>
   </ul>
</div>
-->
<?php
$this->FireEvent('BeforeConversation');
echo $this->Pager->ToString('less');
?>
<ul class="DataList MessageList Conversation">
   <?php
   $MessagesViewLocation = $this->FetchViewLocation('messages');
   include($MessagesViewLocation);
   ?>
</ul>
<?php echo $this->Pager->ToString(); ?>
<div id="MessageForm" class="CommentForm">
   <h2><?php echo T('Add Message'); ?></h2>
   <?php
   echo $this->Form->Open(array('action' => Url('/messages/addmessage/')));
   echo Wrap($this->Form->TextBox('Body', array('MultiLine' => TRUE, 'class' => 'TextBox')), 'div', array('class' => 'TextBoxWrapper'));

   echo '<div class="Buttons">',
      $this->Form->Button('Send Message'),
      '</div>';

   echo $this->Form->Close();
   ?>
</div>
