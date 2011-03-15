<?php if (!defined('APPLICATION')) exit();
$Alt = FALSE;
$Session = Gdn::Session();
$EditUser = $Session->CheckPermission('Garden.Users.Edit');
$DeleteUser = $Session->CheckPermission('Garden.Users.Delete');
foreach ($this->UserData->Format('Text')->Result() as $User) {
   $Alt = $Alt ? FALSE : TRUE;
   ?>
   <tr<?php echo $Alt ? ' class="Alt"' : ''; ?>>
      <td><strong><?php echo UserAnchor($User); ?></strong></td>
      <td class="Alt"><?php echo Gdn_Format::Email($User->Email); ?></td>
      <td style="max-width: 200px;">
         <?php
         $Roles = GetValue('Roles', $User, array());
         $RolesString = '';

         if ($User->Banned && !in_array('Banned', $Roles)) {
            $RolesString = T('Banned');
         }

         foreach ($Roles as $RoleID => $RoleName) {
            $Query = http_build_query(array('Form/Keywords' => $RoleName));
            $RolesString = ConcatSep(', ', $RolesString, '<a href="'.Url('/user/browse?'.$Query).'">'.htmlspecialchars($RoleName).'</a>');
         }
         echo $RolesString;
         ?>
      </td>
      <td class="Alt"><?php echo Gdn_Format::Date($User->DateFirstVisit); ?></td>
      <td><?php echo Gdn_Format::Date($User->DateLastActive); ?></td>
      <?php if ($EditUser || $DeleteUser) { ?>
         <td><?php
         if ($EditUser)
            echo Anchor(T('Edit'), '/user/edit/'.$User->UserID, 'Popup SmallButton');
            
         if ($DeleteUser && $User->UserID != $Session->User->UserID)
            echo Anchor(T('Delete'), '/user/delete/'.$User->UserID, 'SmallButton');
         ?></td>
      <?php } ?>
   </tr>
<?php
}