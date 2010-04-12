<?php if (!defined('APPLICATION')) exit();

echo $this->Form->Open();
?>
<h1><?php echo T('Manage Roles & Permissions'); ?></h1>
<div class="FilterMenu"><?php echo Anchor('Add Role', 'garden/role/add', 'Button'); ?></div>
<table border="0" cellpadding="0" cellspacing="0" class="AltColumns Sortable" id="RoleTable">
   <thead>
      <tr id="0">
         <th><?php echo T('Role'); ?></th>
         <th class="Alt"><?php echo T('Description'); ?></th>
      </tr>
   </thead>
   <tbody>
<?php
$Alt = FALSE;
foreach ($this->RoleData->Result() as $Role) {
   $Alt = $Alt ? FALSE : TRUE;
   ?>
   <tr id="<?php echo $Role->RoleID; ?>"<?php echo $Alt ? ' class="Alt"' : ''; ?>>
      <td class="Info">
         <strong><?php echo $Role->Name; ?></strong>
         <div>
            <?php
            echo Anchor('Edit', '/role/edit/'.$Role->RoleID);
            if ($Role->Deletable) {
            ?>
            <span>|</span>
            <?php
            echo Anchor('Delete', '/role/delete/'.$Role->RoleID, 'Popup');
            }
            ?>
      </td>
      <td class="Alt"><?php echo $Role->Description; ?></td>
   </tr>
<?php } ?>
   </tbody>
</table>
<?php
echo $this->Form->Close();