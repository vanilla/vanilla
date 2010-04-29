<?php if (!defined('APPLICATION')) exit(); ?>
<div class="Box AddPeople">
   <h4><?php echo T('Add People to this Conversation'); ?></h4>
   <?php
      echo $this->Form->Open();
      echo $this->Form->TextBox('AddPeople', array('MultiLine' => TRUE, 'class' => 'MultiComplete'));
      echo $this->Form->Close('Add');
   ?>
</div>