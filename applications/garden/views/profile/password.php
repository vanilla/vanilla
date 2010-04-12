<?php if (!defined('APPLICATION')) exit(); ?>
<h2><?php echo T('Change My Password'); ?></h2>
<?php
echo $this->Form->Open();
echo $this->Form->Errors();
?>
<ul>
   <li>
      <?php
         echo $this->Form->Label('Old Password', 'OldPassword');
         echo $this->Form->Input('OldPassword', 'password');
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
         echo $this->Form->Label('Confirm Password', 'PasswordMatch');
         echo $this->Form->Input('PasswordMatch', 'password');
      ?>
   </li>
</ul>
<?php echo $this->Form->Close('Change Password');