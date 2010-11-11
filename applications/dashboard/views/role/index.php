<?php if (!defined('APPLICATION')) exit();
$Advanced = C('Garden.Roles.Manage');
echo $this->Form->Open();
?>
<h1><?php echo T('Manage Roles & Permissions'); ?></h1>
<?php $this->DefaultRolesWarning(); ?>
<div class="Info"><?php
   echo T('Roles determine user\'s permissions.', 'Every user in your site is assigned to at least one role. Roles are used to determine what the users are allowed to do.');
   $this->FireEvent('AfterRolesInfo');
?></div>
<?php if ($Advanced) { ?>
<div class="FilterMenu"><?php echo Anchor(T('Add Role'), 'dashboard/role/add', 'SmallButton'); ?></div>
<?php } ?>
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
         <?php if ($Advanced) { ?>
         <div>
            <?php
            echo Anchor(T('Edit'), '/role/edit/'.$Role->RoleID, 'SmallButton');
            if ($Role->Deletable)
               echo Anchor(T('Delete'), '/role/delete/'.$Role->RoleID, 'Popup SmallButton');
            ?>
         </div>
         <?php } ?>
      </td>
      <td class="Alt"><?php echo $Role->Description; ?></td>
   </tr>
<?php } ?>
   </tbody>
</table>
<?php
echo $this->Form->Close();