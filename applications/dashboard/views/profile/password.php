<?php if (!defined('APPLICATION')) exit(); ?>
<h2 class="H"><?php echo T('Change My Password'); ?></h2>
<?php
echo $this->Form->Open();
echo $this->Form->Errors();
?>
<ul>
   <li>
      <?php
         // No password may have been set if they have only signed in with a connect plugin
         if (!$this->User->HashMethod || $this->User->HashMethod == "Vanilla") {
	         echo $this->Form->Label('Old Password', 'OldPassword');
	         echo $this->Form->Input('OldPassword', 'password');
         }
      ?>
   </li>
   <li>
      <?php
         echo $this->Form->Label('New Password', 'Password');
         echo $this->Form->Input('Password', 'password', array('Strength' => TRUE));
      ?>
   </li>
   <li>
      <?php
         echo $this->Form->Label('Confirm Password', 'PasswordMatch');
         echo $this->Form->Input('PasswordMatch', 'password');
      ?>
   </li>
</ul>
<?php echo $this->Form->Close('Change Password', '', array('class' => 'Button Primary'));