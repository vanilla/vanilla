<?php if (!defined('APPLICATION')) exit(); ?>
<h1><?php echo T("Reset my password") ?></h1>
<div class="Box">
   <?php
   // Make sure to force this form to post to the correct place in case the view is
   // rendered within another view (ie. /dashboard/entry/index/):
   echo $this->Form->Open(array('Action' => Url('/entry/passwordrequest'), 'id' => 'Form_User_Password'));
   echo $this->Form->Errors(); ?>
   <ul>
      <li>
         <?php
            echo $this->Form->Label('Enter your email address', 'Email');
            echo $this->Form->TextBox('Email');
         ?>
      </li>
      <li class="Buttons">
         <?php
            echo $this->Form->Button('Request a new password');
            echo Wrap(Anchor(T('I remember now!'), '/entry/signin'), 'div');
         ?>
      </li>
   </ul>
   <?php echo $this->Form->Close(); ?>
</div>