<?php if (!defined('APPLICATION')) exit();
$Alt = FALSE;
$Session = Gdn::Session();
$EditUser = $Session->CheckPermission('Garden.Users.Edit');
$DeleteUser = $Session->CheckPermission('Garden.Users.Delete');
foreach ($this->UserData->Result() as $User) {
   $Alt = $Alt ? FALSE : TRUE;
   ?>
   <tr id="<?php echo "UserID_{$User->UserID}"; ?>"<?php echo $Alt ? ' class="Alt"' : ''; ?>>
<!--      <td class="CheckboxCell"><input type="checkbox" name="LogID[]" value="<?php echo $User->UserID; ?>" /></td>-->
      <td><strong><?php echo UserAnchor($User); ?></strong></td>
      <td class="Alt"><?php echo Gdn_Format::Email($User->Email); ?></td>
      <td style="max-width: 200px;">
         <?php
         $Roles = GetValue('Roles', $User, array());
         $RolesString = '';

         if ($User->Banned && !in_array('Banned', $Roles)) {
            $RolesString = T('Banned');
         }
         
         if ($User->Admin > 1) {
            $RolesString = ConcatSep(', ', $RolesString, T('System'));
         }

         foreach ($Roles as $RoleID => $RoleName) {
            $Query = http_build_query(array('Keywords' => $RoleName));
            $RolesString = ConcatSep(', ', $RolesString, '<a href="'.Url('/user/browse?'.$Query).'">'.htmlspecialchars($RoleName).'</a>');
         }
         echo $RolesString;
         ?>
      </td>
      <td class="Alt"><?php echo Gdn_Format::Date($User->DateFirstVisit, 'html'); ?></td>
      <td><?php echo Gdn_Format::Date($User->DateLastActive, 'html'); ?></td>
      <td><?php echo htmlspecialchars($User->LastIPAddress); ?></td>
      <?php
         $this->EventArgs['User'] = $User;
         $this->FireEvent('UserCell');
      ?>
      <?php if ($EditUser || $DeleteUser) { ?>
         <td><?php   
         if ($EditUser)
            echo Anchor(T('Edit'), '/user/edit/'.$User->UserID, 'Popup SmallButton');
            
         if ($DeleteUser && $User->UserID != $Session->User->UserID)
            echo Anchor(T('Delete'), '/user/delete/'.$User->UserID, 'SmallButton');
         
         $this->EventArguments['User'] = $User;
         $this->FireEvent('UserListOptions');
         ?></td>
      <?php } ?>
   </tr>
<?php
}