<?php if (!defined('APPLICATION')) exit(); ?>
<div class="Box AddPeople">
   <?php
      echo panelHeading(T('Add People to this Conversation'));
      echo $this->Form->Open(array('id' => 'Form_AddPeople'));
      echo Wrap($this->Form->TextBox('AddPeople', array('MultiLine' => TRUE, 'class' => 'MultiComplete')), 'div', array('class' => 'TextBoxWrapper'));
      echo $this->Form->Close('Add', '', array('class' => 'Button Action'));
   ?>
</div>