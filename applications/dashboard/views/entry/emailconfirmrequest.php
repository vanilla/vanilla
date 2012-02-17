<?php if (!defined('APPLICATION')) exit(); ?>
<h1><?php echo T("Confirm Email") ?></h1>
<div>
   <?php
   echo $this->Form->Open();
   echo $this->Form->Errors();
   echo '<div class="P">';

   if ($this->Form->ErrorCount() == 0)
      echo T('Your request has been sent.', 'Your request has been sent. Check your email for further instructions.');

   echo '</div>';
   echo $this->Form->Close(); ?>
</div>