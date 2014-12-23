<?php if (!defined('APPLICATION')) exit(); ?>
<div class="FormTitleWrapper ChangeAuthorForm">
   <?php
   echo Wrap($this->Data('Title'), 'h1', array('class' => 'H'));

   echo '<div class="FormWrapper">';
   echo $this->Form->Open();
   echo $this->Form->Errors();

   echo '<div class="P">';
   echo $this->Form->Label('New Author', 'Author');
   echo Wrap($this->Form->TextBox('Author', array('class' => 'MultiComplete')), 'div', array('class' => 'TextBoxWrapper'));
   echo '</div>';

   echo $this->Form->Close('Change Author', '', array('class' => 'Button Primary'));
   echo '</div>';
   ?>
</div>