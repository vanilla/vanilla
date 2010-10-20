<?php if (!defined('APPLICATION')) exit(); ?>
<h1><?php echo T("Reset my password") ?></h1>
<div class="Box">
   <?php
   echo $this->Form->Open();
   echo $this->Form->Errors();
   ?>
   <ul>
      <li>
         <?php
            echo $this->Form->Label('New Password', 'Password');
            echo $this->Form->Input('Password', 'password');
         ?>
      </li>
      <li>
         <?php
            echo $this->Form->Label('Confirm Password', 'Confirm');
            echo $this->Form->Input('Confirm', 'password');
         ?>
      </li>
      <li class="Buttons">
         <?php
            echo $this->Form->Button('Save your password');
         ?>
      </li>
   </ul>
   <?php echo $this->Form->Close(); ?>
</div>