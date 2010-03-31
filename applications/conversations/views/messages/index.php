<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();
?>
<h2><?php echo $this->Participants; ?></h2>
<?php
echo $this->Pager->ToString('less');
?>
<ul id="Conversation">
   <?php
   $MessagesViewLocation = $this->FetchViewLocation('messages');
   include($MessagesViewLocation);
   ?>
</ul>
<?php echo $this->Pager->ToString(); ?>
<div id="MessageForm">
   <h2><?php echo T('Add Message'); ?></h2>
   <?php
   echo $this->Form->Open(array('action' => Url('/messages/addmessage/')));
   echo $this->Form->TextBox('Body', array('MultiLine' => TRUE, 'class' => 'MessageBox'));
   echo $this->Form->Button('Send Message');
   echo $this->Form->Close();
   ?>
</div>
