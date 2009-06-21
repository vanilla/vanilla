<?php if (!defined('APPLICATION')) exit();

echo $this->Form->Open();
?>
<h1><?php echo Gdn::Translate('Manage Roles & Permissions'); ?></h1>
<p><?php echo Anchor('Add Role', 'garden/role/add', 'Button'); ?></p>
<table border="0" cellpadding="0" cellspacing="0" class="AltColumns Sortable" id="RoleTable">
   <thead>
      <tr id="0">
         <th><?php echo Gdn::Translate('Role'); ?></th>
         <th class="Alt"><?php echo Gdn::Translate('Description'); ?></th>
         <th><?php echo Gdn::Translate('Options'); ?></th>
      </tr>
   </thead>
   <tbody>
<?php
$Alt = FALSE;
foreach ($this->RoleData->Result() as $Role) {
   $Alt = $Alt ? FALSE : TRUE;
   ?>
   <tr id="<?php echo $Role->RoleID; ?>"<?php echo $Alt ? ' class="Alt"' : ''; ?>>
      <td><a href="<?php echo Url('/role/edit/'.$Role->RoleID); ?>"><?php echo $Role->Name; ?></a></td>
      <td class="Alt"><?php echo $Role->Description; ?></td>
      <td><?php
         if ($Role->Deletable)
            echo Anchor('Delete', '/role/delete/'.$Role->RoleID);
         else
            echo '&nbsp;';
            
         ?></td>
   </tr>
<?php } ?>
   </tbody>
</table>
<?php
echo $this->Form->Close();