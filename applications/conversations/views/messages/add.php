<?php if (!defined('APPLICATION')) exit(); ?>
<div id="ConversationForm" class="FormTitleWrapper">
   <?php
   echo Wrap($this->Data('Title'), 'h1');
   
   echo '<div class="FormWrapper">'; 
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
   echo '</div>';
   ?>
</div>