<?php if (!defined('APPLICATION')) exit(); ?>
<h1><?php
   if (is_object($this->Role))
      echo T('Edit Role');
   else
      echo T('Add Role');
?></h1>
<?php
echo $this->Form->Open();
echo $this->Form->Errors();
?>
<ul>
   <li>
      <?php
         echo $this->Form->Label('Role Name', 'Name');
         echo $this->Form->TextBox('Name');
      ?>
   </li>
   <li>
      <?php
         echo $this->Form->Label('Description', 'Description');
         echo $this->Form->TextBox('Description', array('MultiLine' => TRUE));
      ?>
   </li>
   <?php
   $this->FireEvent('BeforeRolePermissions');
   
   if (count($this->PermissionData) > 0) {
      if ($this->Role && $this->Role->CanSession != '1') {
         ?>
         <li><p class="Warning"><?php echo T('Heads Up! This is a special role that does not allow active sessions. For this reason, the permission options have been limited to "view" permissions.'); ?></p></li>
         <?php
      }
      ?>
      <li class="RolePermissions">
         <?php
            echo '<strong>'.T('Check all permissions that apply to this role:').'</strong>';
            echo $this->Form->CheckBoxGridGroups($this->PermissionData, 'Permission');
         ?>
      </li>
   <?php
   }
   ?>
</ul>
<?php echo $this->Form->Close('Save'); ?>