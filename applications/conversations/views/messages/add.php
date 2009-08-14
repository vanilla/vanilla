<?php if (!defined('APPLICATION')) exit(); ?>

<div id="ConversationForm">
   <?php
   echo $this->Form->Open();
   echo $this->Form->Label('Start a New Conversation', 'Body', array('class' => 'Heading'));
   echo $this->Form->Errors();
   echo $this->Form->Label('To', 'To');
   echo $this->Form->TextBox('To', array('MultiLine' => TRUE, 'class' => 'MultiComplete'));
   echo $this->Form->TextBox('Body', array('MultiLine' => TRUE));
   echo $this->Form->Close('Start Conversation');
   ?>
</div>