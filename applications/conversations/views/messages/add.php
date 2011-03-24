<?php if (!defined('APPLICATION')) exit();
$this->Title(T('Start a New Conversation'));
?>
<div id="ConversationForm">
   <h1><?php echo T('Start a New Conversation'); ?></h1>
   <?php
   echo $this->Form->Open();
   echo $this->Form->Errors();
   
   echo '<p>', $this->Form->Label('Recipients', 'To');
   echo $this->Form->TextBox('To', array('MultiLine' => TRUE, 'class' => 'MultiComplete')), '</p>';
   
   echo '<p>', $this->Form->TextBox('Body', array('MultiLine' => TRUE)), '</p>';
   echo $this->Form->Close('Start Conversation');
   ?>
</div>