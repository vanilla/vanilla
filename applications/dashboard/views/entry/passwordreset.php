<?php if (!defined('APPLICATION')) exit(); ?>
<h1><?php echo T("Reset my password") ?></h1>
<div class="Box">
   <?php
   echo $this->Form->Open();
   echo $this->Form->Errors();
   ?>

   <?php if (!$this->Data('Fatal')): ?>
   <ul>
      <li>
         <?php
            echo '<div class="Info">', sprintf(T('Resetting the password for %s.'), htmlspecialchars($this->Data('User.Name'))) ,'</div>';
         ?>
      </li>
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
            echo $this->Form->Button('Save your password', array('class' => 'Button Primary'));
         ?>
      </li>
   </ul>
   <?php else: ?>
      <div class="P Center">
         <?php
         echo Anchor(T('Request another password reset.'), '/entry/passwordrequest');
         ?>
      </div>
   <?php endif; ?>
   <?php echo $this->Form->Close(); ?>
</div>