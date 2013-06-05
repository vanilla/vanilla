<?php if (!defined('APPLICATION')) exit(); ?>

<h1><?php echo $this->Data('Title'); ?></h1>
   <?php
   echo $this->Form->Open();
   echo $this->Form->Errors();

   echo Wrap(FormatString(T("ConfirmDeleteProfileField",
      "You are about to delete the profile field &ldquo;{Field.Label}&rdquo; from all users."), $this->Data), 'p');

   echo '<div class="Buttons Buttons-Confirm">';
   echo $this->Form->Button('Delete Field');
   echo $this->Form->Button('Cancel', array('type' => 'button', 'class' => 'Button Close Cancel'));
   echo '</div>';

   echo $this->Form->Close();
   ?>
