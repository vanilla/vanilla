<?php if (!defined('APPLICATION')) exit();
$this->Title(T('Start a New Conversation'));
?>
<div id="ConversationForm">
   <h1><?php echo T('Start a New Conversation'); ?></h1>
   <?php
   echo $this->Form->Open();
   echo $this->Form->Errors();
   
   echo '<div class="P">';
      echo $this->Form->Label('Recipients', 'To');
      echo Wrap($this->Form->TextBox('To', array('MultiLine' => TRUE, 'class' => 'MultiComplete')), 'div', array('class' => 'TextBoxWrapper'));
   echo '</div>';

   if (C('Conversations.Subjects.Visible')) {
      echo '<div class="P">';
         echo $this->Form->Label('Subject', 'Subject');
         echo Wrap(
            $this->Form->TextBox('Subject', array('class' => 'InputBox BigInput')),
            'div',
            array('class' => 'TextBoxWrapper'));
      echo '</div>';
   }
   
   echo '<div class="P">';
      echo Wrap($this->Form->TextBox('Body', array('MultiLine' => TRUE)), 'div', array('class' => 'TextBoxWrapper'));
   echo '</div>';
   
   echo $this->Form->Close('Start Conversation');
   ?>
</div>