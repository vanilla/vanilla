<?php if (!defined('APPLICATION')) exit(); ?>
<div class="Center jsConnect-Connecting" style="margin-top: 25%">
   <div class="Connect-Wait Hidden">
   <h1><?php echo T('Please wait...'); ?></h1>
   <div class="Progress"></div>
   </div>
   <?php
   echo $this->Form->Open(array('id' => 'Form_JsConnect-Connect')), $this->Form->Errors();
   echo $this->Form->Close();
   ?>
</div>