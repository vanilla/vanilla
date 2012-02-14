<?php if (!defined('APPLICATION')) exit(); ?>
<h1><?php echo T("Confirm Email") ?></h1>
<div>
   <?php
   echo $this->Form->Open();
   echo $this->Form->Errors();

   echo '<div class="P">';

   if ($this->Data('EmailConfirmed')) {
      echo T('Your email has been successfully confirmed.');
   } else {
      echo sprintf(T('To send another confirmation email click <a href="%s">here</a>.'), Url('/entry/emailconfirmrequest'));
   }

   echo '</div>';

   echo $this->Form->Close(); ?>
</div>