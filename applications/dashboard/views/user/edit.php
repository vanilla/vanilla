<?php if (!defined('APPLICATION')) exit(); ?>
<h1><?php
   if (is_object($this->User))
      echo T('Edit User');
   else
      echo T('Add User');
?></h1>
<?php
echo $this->Form->Open(array('class' => 'User'));
echo $this->Form->Errors();
?>
<ul>
   <li>
      <?php
         echo $this->Form->Label('Username', 'Name');
         echo $this->Form->TextBox('Name');
      ?>
   </li>
   <li>
      <?php
         echo $this->Form->Label('Email', 'Email');
         echo $this->Form->TextBox('Email');
      ?>
   </li>
   <li>
      <?php
         echo $this->Form->CheckBox('ShowEmail', T('Email visible to other users'), array('value' => '1'));
      ?>
   </li>
   <li>
      <strong><?php echo T('Check all roles that apply to this user:'); ?></strong>
      <?php echo $this->Form->CheckBoxList("RoleID", $this->RoleData, $this->UserRoleData, array('TextField' => 'Name', 'ValueField' => 'RoleID')); ?>
   </li>
</ul>
<h3><?php echo T('Password Options'); ?></h3>
<ul>
   <li>
      <?php
         echo $this->Form->Label('Reset Password');
         echo $this->Form->CheckBox('ResetPassword', T('Reset password and send email notification to user'));
      ?>
   </li>
   <li id="NewPassword">
      <?php
         echo $this->Form->Label('New Password', 'NewPassword');
         echo $this->Form->Input('NewPassword', 'password');
      ?>
      <div class="InputButtons">
         <?php
            echo Anchor(T('Generate Password'), '#', 'GeneratePassword Button SmallButton');
            echo Anchor(T('Reveal Password'), '#', 'RevealPassword Button SmallButton');
         ?>
      </div>
   </li>
</ul>
<?php echo $this->Form->Close('Save');