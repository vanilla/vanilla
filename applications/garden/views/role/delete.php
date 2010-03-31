<?php if (!defined('APPLICATION')) exit(); ?>
<h1><?php echo T('Delete Role'); ?></h1>
<?php
echo $this->Form->Open();
echo $this->Form->Errors();
?>
<ul>
   <li>
      <p class="Warning"><?php echo T("<strong>Heads Up!</strong> Deleting a role can result in users not having access to the application."); ?></p>
      <p><?php printf(T("%s user(s) will be affected by this action."), $this->AffectedUsers); ?></p>
      <?php
      
         if ($this->OrphanedUsers > 0) {
            echo '<p>'.sprintf(T("If you delete this role and don't specify a replacement role, %s user(s) will be orphaned."), $this->OrphanedUsers).'</p>';
         }
         ?>
      <p><?php echo T('Choose a role that orphaned users will be assigned to:'); ?></p>
   </li>
   <li>
      <?php
         echo $this->Form->Label('Replacement Role', 'ReplacementRoleID');
         echo $this->Form->DropDown(
            'ReplacementRoleID',
            $this->ReplacementRoles,
            array(
               'ValueField' => 'RoleID',
               'TextField' => 'Name',
               'IncludeNull' => TRUE
            ));
      ?>
   </li>
</ul>
<?php echo $this->Form->Close('Delete');