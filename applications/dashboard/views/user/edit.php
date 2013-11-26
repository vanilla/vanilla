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
if ($this->Data('AllowEditing')) { ?>
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
            if (UserModel::NoEmail()) {
               echo '<div class="Gloss">',
                  T('Email addresses are disabled.', 'Email addresses are disabled. You can only add an email address if you are an administrator.'),
                  '</div>';
            }
            echo $this->Form->TextBox('Email');
         ?>
      </li>
      <li>
         <?php
            echo $this->Form->CheckBox('ShowEmail', T('Email visible to other users'), array('value' => '1'));
         ?>
      </li>
      <li>
         <?php
            echo $this->Form->CheckBox('Verified', T('Verified Label', 'Verified. Bypasses spam and pre-moderation filters.'), array('value' => '1'));
         ?>
      </li>
      <li>
         <?php
            echo $this->Form->CheckBox('Banned', T('Banned'), array('value' => '1'));
         ?>
      </li>
   </ul>
   <h3><?php echo T('Roles'); ?></h3>
   <ul>
      <li>
         <strong><?php echo T('Check all roles that apply to this user:'); ?></strong>
         <?php 
            //echo $this->Form->CheckBoxList("RoleID", $this->RoleData, $this->UserRoleData, array('TextField' => 'Name', 'ValueField' => 'RoleID')); 
         echo $this->Form->CheckBoxList("RoleID", array_flip($this->RoleData), array_flip($this->UserRoleData)); 
         ?>
      </li>
   </ul>
   <h3><?php echo T('Password Options'); ?></h3>
   <ul>
      <li class="PasswordOptions">
         <?php
            $ResetOptions = array(
               0 => T('Keep current password.'),
               'Auto' => T('Force user to reset their password and send email notification.'),
               'Manual' => T('Manually set user password. No email notification.')
            );
            echo $this->Form->RadioList('ResetPassword', $ResetOptions);
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
<?php 

   $this->FireEvent('AfterFormInputs');
   echo $this->Form->Close('Save');
}