<?php if (!defined('APPLICATION')) exit(); ?>
<h1><?php echo $this->Data('Title'); ?></h1>
<?php
echo $this->Form->Open();
echo $this->Form->Errors();
?>
<ul>
   <li id="NewUserRoles">
      <div><?php echo T('Member roles', 'Check all roles that should be applied to new/approved users'), T(':'); ?></div>
      <?php echo $this->Form->CheckBoxList('DefaultRoles', $this->Data('RoleData'), NULL, array('TextField' => 'Name', 'ValueField' => 'RoleID', 'listclass' => 'ColumnCheckBoxList')); ?>
   </li>
   <li>
      <div><?php echo T('Guest roles', 'Check all roles that should be applied to guests'), T(':'); ?></div>
      <?php echo $this->Form->CheckBoxList('GuestRoles', $this->Data('RoleData'), NULL, array('TextField' => 'Name', 'ValueField' => 'RoleID', 'listclass' => 'ColumnCheckBoxList')); ?>
   </li>
</ul>
<?php echo $this->Form->Close('Save');