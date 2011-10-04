<?php if (!defined('APPLICATION')) exit(); ?>
<h1><?php echo $this->Data('Title'); ?></h1>
<?php
echo $this->Form->Open();
echo $this->Form->Errors();
?>
<ul>
   <li id="NewUserRoles">
      <div><?php echo T('Member roles', 'Check all roles that should be applied to new/approved users.') ?></div>
      <?php echo $this->Form->CheckBoxList('DefaultRoles', $this->Data('RoleData'), NULL, array('TextField' => 'Name', 'ValueField' => 'RoleID', 'listclass' => 'ColumnCheckBoxList')); ?>
   </li>
   <li>
      <div><?php echo T('Guest roles', 'Check all roles that should be applied to guests.') ?></div>
      <?php echo $this->Form->CheckBoxList('GuestRoles', $this->Data('RoleData'), NULL, array('TextField' => 'Name', 'ValueField' => 'RoleID', 'listclass' => 'ColumnCheckBoxList')); ?>
   </li>
   <li>
      <div><?php echo T('Applicant Role', 'Select the role that should be applied for new applicants. This only applies if you have the <b>approval</b> registration method.'); ?></div>
      <?php echo $this->Form->RadioList('ApplicantRoleID', $this->Data('RoleData'), array('TextField' => 'Name', 'ValueField' => 'RoleID', 'list' => TRUE, 'listclass' => 'ColumnCheckBoxList')); ?>
   </li>
</ul>
<?php echo $this->Form->Close('Save');